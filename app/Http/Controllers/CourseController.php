<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\CourseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CourseController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $query = Course::with(['teacher', 'category'])
                      ->where('status', 'active');

        // Apply filters
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $courses = $query->paginate(12);
        $categories = CourseCategory::where('is_active', true)->get();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'categories' => $categories,
            'filters' => $request->only(['category', 'level', 'search']),
        ]);
    }

    public function show(Course $course)
    {
        $course->load(['teacher', 'category', 'lessons.progress' => function ($query) {
            $query->where('student_id', auth()->id());
        }]);

        $isEnrolled = auth()->user()->canAccessCourse($course->id);
        $enrollment = null;

        if ($isEnrolled && auth()->user()->isStudent()) {
            $enrollment = Enrollment::where('student_id', auth()->id())
                                  ->where('course_id', $course->id)
                                  ->first();
        }

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'enrollment' => $enrollment,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Course::class);
        
        $categories = CourseCategory::where('is_active', true)->get();

        return Inertia::render('Courses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Course::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'nullable|exists:course_categories,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'max_students' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'prerequisites' => 'nullable|array',
            'learning_objectives' => 'nullable|array',
        ]);

        $validated['teacher_id'] = auth()->id();
        $validated['status'] = 'draft';

        $course = Course::create($validated);

        return redirect()->route('courses.show', $course)
                        ->with('success', 'Course created successfully!');
    }

    public function edit(Course $course)
    {
        $this->authorize('update', $course);

        $categories = CourseCategory::where('is_active', true)->get();

        return Inertia::render('Courses/Edit', [
            'course' => $course,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'nullable|exists:course_categories,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'max_students' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:draft,active,archived',
            'prerequisites' => 'nullable|array',
            'learning_objectives' => 'nullable|array',
        ]);

        $course->update($validated);

        return redirect()->route('courses.show', $course)
                        ->with('success', 'Course updated successfully!');
    }

    public function enroll(Course $course)
    {
        $user = auth()->user();

        if (!$user->isStudent()) {
            return redirect()->back()->with('error', 'Only students can enroll in courses.');
        }

        if (!$course->hasAvailableSlots()) {
            return redirect()->back()->with('error', 'This course is full.');
        }

        $existingEnrollment = Enrollment::where('student_id', $user->id)
                                       ->where('course_id', $course->id)
                                       ->first();

        if ($existingEnrollment) {
            return redirect()->back()->with('error', 'You are already enrolled in this course.');
        }

        return Inertia::render('Courses/Enroll', [
            'course' => $course->load('teacher'),
        ]);
    }

    public function processEnrollment(Request $request, Course $course)
    {
        $user = auth()->user();

        if (!$user->isStudent()) {
            return redirect()->back()->with('error', 'Only students can enroll in courses.');
        }

        // Check if course requires payment
        if ($course->price > 0) {
            // Handle payment process here
            // For now, we'll create enrollment with pending payment status
            $paymentStatus = 'pending';
        } else {
            $paymentStatus = 'paid';
        }

        Enrollment::create([
            'student_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
            'payment_status' => $paymentStatus,
        ]);

        return redirect()->route('courses.show', $course)
                        ->with('success', 'Successfully enrolled in the course!');
    }

    public function teacherCourses(Request $request)
    {
        $user = $request->user();
        $courses = Course::where('teacher_id', $user->id)
                        ->with(['enrollments', 'assignments'])
                        ->latest()
                        ->paginate(10);

        return Inertia::render('Teacher/Courses', [
            'courses' => $courses,
        ]);
    }

    public function studentCourses(Request $request)
    {
        $user = $request->user();
        $enrollments = Enrollment::where('student_id', $user->id)
                                ->with(['course.teacher', 'course.assignments'])
                                ->latest()
                                ->paginate(10);

        return Inertia::render('Student/Courses', [
            'enrollments' => $enrollments,
        ]);
    }

    public function childCourses(Request $request, User $child)
    {
        $user = $request->user();

        // Verify this is the parent's child
        if ($child->parent_id !== $user->id) {
            abort(403, 'You can only view your own child\'s courses.');
        }

        $enrollments = Enrollment::where('student_id', $child->id)
                                ->with(['course.teacher'])
                                ->latest()
                                ->paginate(10);

        return Inertia::render('Parent/ChildCourses', [
            'child' => $child,
            'enrollments' => $enrollments,
        ]);
    }
}