<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();
        
        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // If user doesn't have required role, redirect based on their role
        return $this->redirectBasedOnRole($user);
    }

    private function redirectBasedOnRole($user)
    {
        if ($user->isStudent()) {
            return redirect('/student/dashboard');
        } elseif ($user->isParent()) {
            return redirect('/parent/dashboard');
        } elseif ($user->isTeacher()) {
            return redirect('/teacher/dashboard');
        } elseif ($user->isAdmin()) {
            return redirect('/admin/dashboard');
        }

        return redirect('/dashboard');
    }
}