<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ChatController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get conversation partners based on user role
        $conversations = $this->getConversationPartners($user);

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function conversation(Request $request, User $partner)
    {
        $user = $request->user();
        
        // Verify access permissions
        $this->verifyConversationAccess($user, $partner);

        // Get messages between users
        $messages = ChatMessage::betweenUsers($user->id, $partner->id)
                              ->visibleTo($user->id)
                              ->with(['sender', 'receiver'])
                              ->latest()
                              ->take(50)
                              ->get()
                              ->reverse()
                              ->values();

        // Mark messages as read
        ChatMessage::where('sender_id', $partner->id)
                  ->where('receiver_id', $user->id)
                  ->whereNull('read_at')
                  ->update(['read_at' => now()]);

        return Inertia::render('Chat/Conversation', [
            'partner' => $partner,
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, User $partner)
    {
        $user = $request->user();
        
        // Verify access permissions
        $this->verifyConversationAccess($user, $partner);

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'type' => 'in:text,image,file',
            'attachments' => 'nullable|array',
        ]);

        $message = ChatMessage::create([
            'sender_id' => $user->id,
            'receiver_id' => $partner->id,
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'text',
            'attachments' => $validated['attachments'] ?? null,
        ]);

        $message->load(['sender', 'receiver']);

        return response()->json([
            'message' => $message,
            'success' => true,
        ]);
    }

    public function markAsRead(Request $request, ChatMessage $message)
    {
        $user = $request->user();

        // Verify user can mark this message as read
        if ($message->receiver_id !== $user->id) {
            abort(403, 'You can only mark your own messages as read.');
        }

        $message->markAsRead();

        return response()->json(['success' => true]);
    }

    public function parentChildChat(Request $request, User $child)
    {
        $user = $request->user();

        // Verify this is a parent-child relationship
        if (!$user->isParent() || $child->parent_id !== $user->id) {
            abort(403, 'You can only chat with your own children.');
        }

        return $this->conversation($request, $child);
    }

    private function getConversationPartners(User $user)
    {
        $partners = collect();

        if ($user->isParent()) {
            // Parents can chat with their children
            $children = User::where('parent_id', $user->id)->get();
            $partners = $partners->merge($children);
        }

        if ($user->isStudent()) {
            // Students can chat with their parent
            if ($user->parent) {
                $partners->push($user->parent);
            }
            
            // Students can chat with teachers of their enrolled courses
            $teacherIds = $user->studentEnrollments()
                              ->with('course.teacher')
                              ->get()
                              ->pluck('course.teacher.id')
                              ->unique();
            
            $teachers = User::whereIn('id', $teacherIds)->get();
            $partners = $partners->merge($teachers);
        }

        if ($user->isTeacher()) {
            // Teachers can chat with students in their courses
            $studentIds = $user->teacherCourses()
                               ->with('students')
                               ->get()
                               ->pluck('students')
                               ->flatten()
                               ->pluck('id')
                               ->unique();
            
            $students = User::whereIn('id', $studentIds)->get();
            $partners = $partners->merge($students);
        }

        if ($user->isAdmin()) {
            // Admins can chat with everyone
            $partners = User::where('id', '!=', $user->id)->get();
        }

        // Add last message and unread count for each partner
        return $partners->map(function ($partner) use ($user) {
            $lastMessage = ChatMessage::betweenUsers($user->id, $partner->id)
                                     ->visibleTo($user->id)
                                     ->latest()
                                     ->first();

            $unreadCount = ChatMessage::where('sender_id', $partner->id)
                                     ->where('receiver_id', $user->id)
                                     ->unread()
                                     ->count();

            return [
                'user' => $partner,
                'lastMessage' => $lastMessage,
                'unreadCount' => $unreadCount,
            ];
        })->sortByDesc('lastMessage.created_at')->values();
    }

    private function verifyConversationAccess(User $user, User $partner)
    {
        $hasAccess = false;

        if ($user->isAdmin()) {
            $hasAccess = true;
        } elseif ($user->isParent() && $partner->parent_id === $user->id) {
            // Parent accessing child
            $hasAccess = true;
        } elseif ($user->isStudent() && $user->parent_id === $partner->id) {
            // Student accessing parent
            $hasAccess = true;
        } elseif ($user->isStudent() && $partner->isTeacher()) {
            // Student accessing teacher (if enrolled in teacher's course)
            $hasAccess = $user->studentEnrollments()
                             ->whereHas('course', function ($query) use ($partner) {
                                 $query->where('teacher_id', $partner->id);
                             })
                             ->exists();
        } elseif ($user->isTeacher() && $partner->isStudent()) {
            // Teacher accessing student (if student is enrolled in teacher's course)
            $hasAccess = $partner->studentEnrollments()
                               ->whereHas('course', function ($query) use ($user) {
                                   $query->where('teacher_id', $user->id);
                               })
                               ->exists();
        }

        if (!$hasAccess) {
            abort(403, 'You do not have permission to chat with this user.');
        }
    }
}