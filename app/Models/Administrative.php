<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'certificate_number',
        'issued_at',
        'expires_at',
        'status',
        'template_id',
        'grade_average',
        'completion_date',
        'file_path',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'completion_date' => 'date',
            'grade_average' => 'decimal:2',
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

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function request()
    {
        return $this->belongsTo(CertificateRequest::class, 'request_id');
    }

    public function generateCertificateNumber()
    {
        $year = date('Y');
        $courseCode = strtoupper(substr($this->course->title, 0, 3));
        $studentId = str_pad($this->student_id, 4, '0', STR_PAD_LEFT);
        $sequence = str_pad($this->id, 4, '0', STR_PAD_LEFT);
        
        return "CERT-{$year}-{$courseCode}-{$studentId}-{$sequence}";
    }

    public function isValid()
    {
        return $this->status === 'active' && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function getDownloadUrlAttribute()
    {
        return $this->file_path ? route('certificates.download', $this->id) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (!$certificate->certificate_number) {
                // Temporary number, will be updated after creation
                $certificate->certificate_number = 'TEMP-' . time();
            }
        });

        static::created(function ($certificate) {
            $certificate->update([
                'certificate_number' => $certificate->generateCertificateNumber()
            ]);
        });
    }
}

class CertificateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'parent_id',
        'status',
        'requested_at',
        'processed_at',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
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

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class, 'request_id');
    }

    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'processed_at' => now(),
        ]);

        // Generate certificate
        $certificate = Certificate::create([
            'student_id' => $this->student_id,
            'course_id' => $this->course_id,
            'request_id' => $this->id,
            'issued_at' => now(),
            'status' => 'active',
            'grade_average' => $this->calculateGradeAverage(),
            'completion_date' => $this->getCompletionDate(),
        ]);

        return $certificate;
    }

    public function reject($reason)
    {
        $this->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    private function calculateGradeAverage()
    {
        return Grade::where('student_id', $this->student_id)
                   ->where('course_id', $this->course_id)
                   ->avg('percentage');
    }

    private function getCompletionDate()
    {
        $enrollment = Enrollment::where('student_id', $this->student_id)
                                ->where('course_id', $this->course_id)
                                ->first();
        
        return $enrollment ? $enrollment->completed_at : null;
    }
}

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'template_file',
        'is_default',
        'is_active',
        'variables',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'variables' => 'array',
        ];
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_id',
        'gateway_response',
        'status',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'gateway_response' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function isSuccessful()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isRefunded()
    {
        return $this->refunded_at !== null;
    }

    public function canBeRefunded()
    {
        return $this->isSuccessful() && 
               !$this->isRefunded() && 
               $this->paid_at->diffInDays(now()) <= 30; // 30-day refund policy
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}