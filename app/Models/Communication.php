<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'type',
        'attachments',
        'read_at',
        'deleted_by_sender',
        'deleted_by_receiver',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'attachments' => 'array',
            'deleted_by_sender' => 'boolean',
            'deleted_by_receiver' => 'boolean',
        ];
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isUnread()
    {
        return $this->read_at === null;
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeBetweenUsers($query, $user1Id, $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user1Id)->where('receiver_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user2Id)->where('receiver_id', $user1Id);
        });
    }

    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)->where('deleted_by_sender', false);
        })->orWhere(function ($q) use ($userId) {
            $q->where('receiver_id', $userId)->where('deleted_by_receiver', false);
        });
    }
}

class Forum extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'creator_id',
        'is_active',
        'is_private',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_private' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function posts()
    {
        return $this->hasMany(ForumPost::class);
    }

    public function getLatestPost()
    {
        return $this->posts()->with('user')->latest()->first();
    }

    public function getTotalPosts()
    {
        return $this->posts()->count();
    }

    public function getTotalReplies()
    {
        return $this->posts()->whereNotNull('parent_id')->count();
    }
}

class ForumPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'forum_id',
        'user_id',
        'parent_id',
        'title',
        'content',
        'attachments',
        'is_pinned',
        'is_locked',
        'upvotes',
        'downvotes',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function forum()
    {
        return $this->belongsTo(Forum::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(ForumPost::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumPost::class, 'parent_id');
    }

    public function votes()
    {
        return $this->hasMany(ForumVote::class);
    }

    public function isReply()
    {
        return $this->parent_id !== null;
    }

    public function getNetVotes()
    {
        return $this->upvotes - $this->downvotes;
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}

class ForumVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'forum_post_id',
        'user_id',
        'vote_type', // 'up' or 'down'
    ];

    public function post()
    {
        return $this->belongsTo(ForumPost::class, 'forum_post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}