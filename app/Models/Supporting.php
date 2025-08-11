<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }

    public function getActiveCourses()
    {
        return $this->courses()->where('status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'content',
        'video_url',
        'attachments',
        'duration_minutes',
        'sort_order',
        'is_published',
        'is_free_preview',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_published' => 'boolean',
            'is_free_preview' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function getProgressForStudent($studentId)
    {
        return $this->progress()->where('student_id', $studentId)->first();
    }

    public function isCompletedByStudent($studentId)
    {
        $progress = $this->getProgressForStudent($studentId);
        return $progress && $progress->completed_at !== null;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}

class LessonProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'student_id',
        'started_at',
        'completed_at',
        'watch_time_minutes',
        'completion_percentage',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function markAsCompleted()
    {
        $this->update([
            'completed_at' => now(),
            'completion_percentage' => 100,
        ]);
    }

    public function isCompleted()
    {
        return $this->completed_at !== null;
    }
}

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'data',
        'read_at',
        'action_url',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}

class AIRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'recommendation_data',
        'confidence_score',
        'reasoning',
        'status',
        'acted_upon_at',
        'feedback_rating',
    ];

    protected function casts(): array
    {
        return [
            'recommendation_data' => 'array',
            'confidence_score' => 'decimal:2',
            'acted_upon_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsActedUpon()
    {
        $this->update([
            'status' => 'acted_upon',
            'acted_upon_at' => now(),
        ]);
    }

    public function provideFeedback($rating)
    {
        $this->update(['feedback_rating' => $rating]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}