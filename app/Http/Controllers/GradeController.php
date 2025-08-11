<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Grade;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GradeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isStudent()) {
            return $this->studentGrades($request);
        } elseif ($user->isTeacher()) {
            return $this->teacherGrades($request);
        } elseif ($user->isParent()) {
            return $this->parentGradesOverview($request);
        } else {
            abort(403);
        }
    }

    public function studentGrades(Request $request)
    {
        $user = $request->user();
        
        $grades = Grade::where('student_id', $user->id)
                      ->with(['course', 'assignment', 'teacher'])
                      ->latest()
                      ->paginate(15);

        $courseAverages = Grade::where('student_id', $user->id)
                             ->selectRaw('course_id, AVG(percentage) as average')
                             ->groupBy('course_id')
                             ->with('course')
                             ->get();

        $overallAverage = Grade::where('student_id', $user->id)->avg('percentage');

        return Inertia::render('Student/Grades', [
            'grades' => $grades,
            'courseAverages' => $courseAverages,
            'overallAverage' => round($overallAverage, 2),
        ]);
    }

    public function teacherGrades(Request $request)
    {
        $user = $request->user();
        
        $query = Grade::whereHas('course', function ($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })->with(['student', 'course', 'assignment']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $grades = $query->latest()->paginate(20);
        
        $courses = Course::where('teacher_id', $user->id)->get();

        return Inertia::render('Teacher/Grades', [
            'grades' => $grades,
            'courses' => $courses,
            'filters' => $request->only(['course_id']),
        ]);
    }

    public function parentGradesOverview(Request $request)
    {
        $user = $request->user();
        $children = User::where('parent_id', $user->id)->get();

        $childrenGrades = $children->map(function ($child) {
            $grades = Grade::where('student_id', $child->id)
                          ->with(['course', 'assignment'])
                          ->latest()
                          ->take(10)
                          ->get();

            $average = Grade::where('student_id', $child->id)->avg('percentage');

            return [
                'child' => $child,
                'grades' => $grades,
                'average' => round($average, 2),
            ];
        });

        return Inertia::render('Parent/GradesOverview', [
            'childrenGrades' => $childrenGrades,
        ]);
    }

    public function courseGrades(Course $course)
    {
        $user = auth()->user();

        if ($user->isStudent()) {
            // Student viewing their grades for a specific course
            if (!$user->canAccessCourse($course->id)) {
                abort(403, 'You do not have access to this course.');
            }

            $grades = Grade::where('student_id', $user->id)
                          ->where('course_id', $course->id)
                          ->with(['assignment', 'teacher'])
                          ->latest()
                          ->get();

            $average = $grades->avg('percentage');

        } elseif ($user->isTeacher()) {
            // Teacher viewing all grades for their course
            if ($course->teacher_id !== $user->id) {
                abort(403, 'You can only view grades for your own courses.');
            }

            $grades = Grade::where('course_id', $course->id)
                          ->with(['student', 'assignment'])
                          ->latest()
                          ->get();

            $average = $grades->avg('percentage');

        } else {
            abort(403);
        }

        return Inertia::render('Grades/Course', [
            'course' => $course,
            'grades' => $grades,
            'average' => round($average, 2),
        ]);
    }

    public function childGrades(Request $request, User $child)
    {
        $user = $request->user();

        // Verify this is the parent's child
        if ($child->parent_id !== $user->id) {
            abort(403, 'You can only view your own child\'s grades.');
        }

        $grades = Grade::where('student_id', $child->id)
                      ->with(['course', 'assignment', 'teacher'])
                      ->latest()
                      ->paginate(15);

        $courseAverages = Grade::where('student_id', $child->id)
                             ->selectRaw('course_id, AVG(percentage) as average')
                             ->groupBy('course_id')
                             ->with('course')
                             ->get();

        $overallAverage = Grade::where('student_id', $child->id)->avg('percentage');

        return Inertia::render('Parent/ChildGrades', [
            'child' => $child,
            'grades' => $grades,
            'courseAverages' => $courseAverages,
            'overallAverage' => round($overallAverage, 2),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Only teachers and admins can create grades.');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'assignment_id' => 'nullable|exists:assignments,id',
            'points_earned' => 'required|numeric|min:0',
            'max_points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string|max:1000',
        ]);

        // Verify teacher can grade this course
        if ($user->isTeacher()) {
            $course = Course::findOrFail($validated['course_id']);
            if ($course->teacher_id !== $user->id) {
                abort(403, 'You can only grade students in your own courses.');
            }
        }

        $validated['teacher_id'] = $user->id;
        $validated['graded_at'] = now();

        $grade = Grade::create($validated);

        return redirect()->back()->with('success', 'Grade created successfully!');
    }

    public function update(Request $request, Grade $grade)
    {
        $user = $request->user();

        // Verify permissions
        if ($user->isTeacher() && $grade->teacher_id !== $user->id) {
            abort(403, 'You can only update grades you created.');
        } elseif (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Only teachers and admins can update grades.');
        }

        $validated = $request->validate([
            'points_earned' => 'required|numeric|min:0',
            'max_points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $grade->update($validated);

        return redirect()->back()->with('success', 'Grade updated successfully!');
    }

    public function destroy(Grade $grade)
    {
        $user = auth()->user();

        // Verify permissions
        if ($user->isTeacher() && $grade->teacher_id !== $user->id) {
            abort(403, 'You can only delete grades you created.');
        } elseif (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Only teachers and admins can delete grades.');
        }

        $grade->delete();

        return redirect()->back()->with('success', 'Grade deleted successfully!');
    }
}