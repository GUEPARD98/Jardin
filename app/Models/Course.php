<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'teacher_id',
        'category_id',
        'level',
        'duration_hours',
        'price',
        'thumbnail',
        'status',
        'max_students',
        'start_date',
        'end_date',
        'is_featured',
        'prerequisites',
        'learning_objectives',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_featured' => 'boolean',
            'prerequisites' => 'array',
            'learning_objectives' => 'array',
            'price' => 'decimal:2',
        ];
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'student_id')
                   ->withPivot('enrolled_at', 'completed_at', 'progress')
                   ->withTimestamps();
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function forums()
    {
        return $this->hasMany(Forum::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Utility methods
    public function getEnrolledStudentsCount()
    {
        return $this->enrollments()->count();
    }

    public function hasAvailableSlots()
    {
        return $this->max_students === null || $this->getEnrolledStudentsCount() < $this->max_students;
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function getProgressForStudent($studentId)
    {
        $enrollment = $this->enrollments()->where('student_id', $studentId)->first();
        return $enrollment ? $enrollment->progress : 0;
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/courses/' . $this->thumbnail) : asset('images/default-course.jpg');
    }

    public function getAverageRating()
    {
        // Implementation for course ratings when review system is added
        return 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }
}