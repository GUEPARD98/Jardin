<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'assignment_id',
        'teacher_id',
        'points_earned',
        'max_points',
        'percentage',
        'letter_grade',
        'feedback',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'graded_at' => 'datetime',
            'points_earned' => 'decimal:2',
            'max_points' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function calculateLetterGrade()
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 97) return 'A+';
        if ($percentage >= 93) return 'A';
        if ($percentage >= 90) return 'A-';
        if ($percentage >= 87) return 'B+';
        if ($percentage >= 83) return 'B';
        if ($percentage >= 80) return 'B-';
        if ($percentage >= 77) return 'C+';
        if ($percentage >= 73) return 'C';
        if ($percentage >= 70) return 'C-';
        if ($percentage >= 67) return 'D+';
        if ($percentage >= 65) return 'D';
        
        return 'F';
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($grade) {
            if ($grade->points_earned !== null && $grade->max_points > 0) {
                $grade->percentage = ($grade->points_earned / $grade->max_points) * 100;
                $grade->letter_grade = $grade->calculateLetterGrade();
            }
        });
    }
}

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'enrolled_at',
        'completed_at',
        'progress',
        'status',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'progress' => 'decimal:2',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function isCompleted()
    {
        return $this->completed_at !== null;
    }

    public function updateProgress()
    {
        $totalAssignments = $this->course->assignments()->count();
        if ($totalAssignments === 0) {
            $this->progress = 0;
            $this->save();
            return;
        }

        $completedAssignments = $this->course->assignments()
            ->whereHas('submissions', function ($query) {
                $query->where('student_id', $this->student_id)
                      ->where('status', 'submitted');
            })
            ->count();

        $this->progress = ($completedAssignments / $totalAssignments) * 100;
        
        if ($this->progress >= 100 && !$this->isCompleted()) {
            $this->completed_at = now();
            $this->status = 'completed';
        }

        $this->save();
    }
}