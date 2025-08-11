<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Courses
    Route::resource('courses', CourseController::class);
    Route::get('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::post('/courses/{course}/enrollment', [CourseController::class, 'processEnrollment'])->name('courses.process-enrollment');

    // Assignments
    Route::resource('assignments', AssignmentController::class);
    Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->name('assignments.submit');

    // Grades
    Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
    Route::get('/grades/{course}', [GradeController::class, 'courseGrades'])->name('grades.course');
    
    // Chat System
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{user}', [ChatController::class, 'conversation'])->name('chat.conversation');
    Route::post('/chat/{user}/message', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/messages/{message}/read', [ChatController::class, 'markAsRead'])->name('chat.mark-read');

    // Forums
    Route::resource('forums', ForumController::class);
    Route::resource('forums.posts', ForumController::class);
    Route::post('/forum-posts/{post}/vote', [ForumController::class, 'vote'])->name('forum-posts.vote');

    // Educational Games
    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');
    Route::post('/games/{game}/start', [GameController::class, 'startSession'])->name('games.start');
    Route::post('/games/sessions/{session}/result', [GameController::class, 'saveResult'])->name('games.save-result');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates/request', [CertificateController::class, 'request'])->name('certificates.request');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/process', [PaymentController::class, 'process'])->name('payments.process');
    Route::get('/payments/{payment}/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
});

// Role-specific routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/courses', [AdminController::class, 'courses'])->name('courses.index');
    Route::get('/payments', [AdminController::class, 'payments'])->name('payments.index');
    Route::get('/certificates', [AdminController::class, 'certificates'])->name('certificates.index');
    Route::post('/certificates/{request}/approve', [AdminController::class, 'approveCertificate'])->name('certificates.approve');
    Route::post('/certificates/{request}/reject', [AdminController::class, 'rejectCertificate'])->name('certificates.reject');
});

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'teacherDashboard'])->name('dashboard');
    Route::get('/courses', [CourseController::class, 'teacherCourses'])->name('courses.index');
    Route::get('/assignments', [AssignmentController::class, 'teacherAssignments'])->name('assignments.index');
    Route::get('/grades', [GradeController::class, 'teacherGrades'])->name('grades.index');
    Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
    Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'studentDashboard'])->name('dashboard');
    Route::get('/courses', [CourseController::class, 'studentCourses'])->name('courses.index');
    Route::get('/assignments', [AssignmentController::class, 'studentAssignments'])->name('assignments.index');
    Route::get('/grades', [GradeController::class, 'studentGrades'])->name('grades.index');
    Route::get('/games', [GameController::class, 'studentGames'])->name('games.index');
});

Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'parentDashboard'])->name('dashboard');
    Route::get('/children', [DashboardController::class, 'children'])->name('children.index');
    Route::get('/children/{child}/grades', [GradeController::class, 'childGrades'])->name('children.grades');
    Route::get('/children/{child}/assignments', [AssignmentController::class, 'childAssignments'])->name('children.assignments');
    Route::get('/children/{child}/courses', [CourseController::class, 'childCourses'])->name('children.courses');
    Route::get('/chat/{child}', [ChatController::class, 'parentChildChat'])->name('chat.child');
});

require __DIR__.'/auth.php';