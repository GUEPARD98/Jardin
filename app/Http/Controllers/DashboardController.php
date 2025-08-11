<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Enrollment;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        switch ($user->role) {
            case 'admin':
                return $this->adminDashboard($request);
            case 'teacher':
                return $this->teacherDashboard($request);
            case 'student':
                return $this->studentDashboard($request);
            case 'parent':
                return $this->parentDashboard($request);
            default:
                return redirect()->route('login');
        }
    }

    public function adminDashboard(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'total_students' => User::where('role', 'student')->count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_parents' => User::where('role', 'parent')->count(),
            'total_courses' => Course::count(),
            'active_courses' => Course::where('status', 'active')->count(),
            'total_assignments' => Assignment::count(),
            'pending_grades' => Assignment::whereDoesntHave('grades')->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentCourses = Course::with('teacher')->latest()->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentCourses' => $recentCourses,
        ]);
    }

    public function teacherDashboard(Request $request)
    {
        $user = $request->user();
        
        $myCourses = Course::where('teacher_id', $user->id)
                          ->with(['enrollments', 'assignments'])
                          ->get();

        $totalStudents = $myCourses->sum(function ($course) {
            return $course->enrollments->count();
        });

        $pendingAssignments = Assignment::where('teacher_id', $user->id)
                                       ->whereHas('submissions', function ($query) {
                                           $query->where('status', 'submitted');
                                       })
                                       ->with(['course', 'submissions'])
                                       ->take(5)
                                       ->get();

        $stats = [
            'total_courses' => $myCourses->count(),
            'total_students' => $totalStudents,
            'pending_assignments' => $pendingAssignments->count(),
            'total_assignments' => Assignment::where('teacher_id', $user->id)->count(),
        ];

        return Inertia::render('Teacher/Dashboard', [
            'stats' => $stats,
            'courses' => $myCourses,
            'pendingAssignments' => $pendingAssignments,
        ]);
    }

    public function studentDashboard(Request $request)
    {
        $user = $request->user();
        
        $enrollments = Enrollment::where('student_id', $user->id)
                                ->with(['course.teacher'])
                                ->get();

        $pendingAssignments = Assignment::whereHas('course.enrollments', function ($query) use ($user) {
                                        $query->where('student_id', $user->id);
                                    })
                                    ->whereDoesntHave('submissions', function ($query) use ($user) {
                                        $query->where('student_id', $user->id);
                                    })
                                    ->with('course')
                                    ->take(5)
                                    ->get();

        $recentGrades = Grade::where('student_id', $user->id)
                           ->with(['course', 'assignment'])
                           ->latest()
                           ->take(5)
                           ->get();

        $stats = [
            'enrolled_courses' => $enrollments->count(),
            'completed_courses' => $enrollments->where('status', 'completed')->count(),
            'pending_assignments' => $pendingAssignments->count(),
            'total_points' => $user->getTotalPoints(),
        ];

        return Inertia::render('Student/Dashboard', [
            'stats' => $stats,
            'enrollments' => $enrollments,
            'pendingAssignments' => $pendingAssignments,
            'recentGrades' => $recentGrades,
        ]);
    }

    public function parentDashboard(Request $request)
    {
        $user = $request->user();
        
        $children = User::where('parent_id', $user->id)->get();
        
        $childrenData = $children->map(function ($child) {
            $enrollments = Enrollment::where('student_id', $child->id)
                                   ->with('course')
                                   ->get();
            
            $recentGrades = Grade::where('student_id', $child->id)
                                ->with(['course', 'assignment'])
                                ->latest()
                                ->take(3)
                                ->get();

            return [
                'child' => $child,
                'enrollments' => $enrollments,
                'recentGrades' => $recentGrades,
                'totalPoints' => $child->getTotalPoints(),
            ];
        });

        $stats = [
            'total_children' => $children->count(),
            'total_courses' => $children->sum(function ($child) {
                return Enrollment::where('student_id', $child->id)->count();
            }),
            'total_points' => $children->sum(function ($child) {
                return $child->getTotalPoints();
            }),
        ];

        return Inertia::render('Parent/Dashboard', [
            'stats' => $stats,
            'childrenData' => $childrenData,
            'children' => $children,
        ]);
    }

    public function children(Request $request)
    {
        $user = $request->user();
        $children = User::where('parent_id', $user->id)
                       ->with(['studentEnrollments.course', 'grades.course'])
                       ->get();

        return Inertia::render('Parent/Children', [
            'children' => $children,
        ]);
    }
}