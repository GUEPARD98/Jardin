<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        // Check specific permissions based on the request
        switch ($permission) {
            case 'access_course':
                $courseId = $request->route('course')?->id ?? $request->get('course_id');
                if ($courseId && !$user->canAccessCourse($courseId)) {
                    abort(403, 'You do not have access to this course.');
                }
                break;

            case 'manage_grades':
                if (!$user->isTeacher() && !$user->isAdmin()) {
                    abort(403, 'You do not have permission to manage grades.');
                }
                break;

            case 'view_child_data':
                $studentId = $request->route('student')?->id ?? $request->get('student_id');
                if ($user->isParent() && $studentId) {
                    $isChild = $user->children()->where('id', $studentId)->exists();
                    if (!$isChild && !$user->isAdmin()) {
                        abort(403, 'You can only view your own child\'s data.');
                    }
                }
                break;

            case 'access_admin_panel':
                if (!$user->isAdmin()) {
                    abort(403, 'Admin access required.');
                }
                break;

            case 'teacher_resources':
                if (!$user->isTeacher() && !$user->isAdmin()) {
                    abort(403, 'Teacher access required.');
                }
                break;

            case 'student_only':
                if (!$user->isStudent()) {
                    abort(403, 'Student access only.');
                }
                break;

            case 'parent_only':
                if (!$user->isParent()) {
                    abort(403, 'Parent access only.');
                }
                break;

            default:
                // Custom permission logic can be added here
                break;
        }

        return $next($request);
    }
}