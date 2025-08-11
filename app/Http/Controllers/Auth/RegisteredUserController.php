<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:student,parent,teacher',
            'parent_email' => 'required_if:role,student|nullable|email|exists:users,email',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date|before:today',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'is_active' => true,
            'profile_completed' => false,
        ];

        // If registering as a student, link to parent
        if ($request->role === 'student' && $request->parent_email) {
            $parent = User::where('email', $request->parent_email)
                         ->where('role', 'parent')
                         ->first();
            
            if ($parent) {
                $userData['parent_id'] = $parent->id;
            }
        }

        $user = User::create($userData);

        event(new Registered($user));

        Auth::login($user);

        // Redirect based on role
        switch ($user->role) {
            case 'teacher':
                return redirect()->route('teacher.dashboard');
            case 'student':
                return redirect()->route('student.dashboard');
            case 'parent':
                return redirect()->route('parent.dashboard');
            default:
                return redirect()->route('dashboard');
        }
    }
}