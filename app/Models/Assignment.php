<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'teacher_id',
        'type',
        'due_date',
        'max_points',
        'instructions',
        'attachments',
        'is_published',
        'allow_late_submission',
        'late_penalty_percent',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'is_published' => 'boolean',
            'allow_late_submission' => 'boolean',
            'attachments' => 'array',
            'instructions' => 'array',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function getSubmissionForStudent($studentId)
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }

    public function isOverdue()
    {
        return $this->due_date && now()->greaterThan($this->due_date);
    }

    public function getSubmissionStatus($studentId)
    {
        $submission = $this->getSubmissionForStudent($studentId);
        
        if (!$submission) {
            return $this->isOverdue() ? 'overdue' : 'pending';
        }

        return $submission->status;
    }
}

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'content',
        'attachments',
        'submitted_at',
        'status',
        'teacher_feedback',
        'grade_points',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grade()
    {
        return $this->hasOne(Grade::class, 'assignment_id', 'assignment_id')
                   ->where('student_id', $this->student_id);
    }

    public function isLateSubmission()
    {
        return $this->submitted_at && $this->assignment->due_date && 
               $this->submitted_at->greaterThan($this->assignment->due_date);
    }
}