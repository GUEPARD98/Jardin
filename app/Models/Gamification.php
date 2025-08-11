<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GamificationPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'points',
        'reason',
        'activity_type',
        'activity_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'points_required',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function userBadges()
    {
        return $this->hasMany(UserBadge::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
                   ->withTimestamps();
    }
}

class UserBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'badge_id',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function badge()
    {
        return $this->belongsTo(Badge::class);
    }
}

class EducationalGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'level',
        'course_id',
        'game_type',
        'config',
        'thumbnail',
        'instructions',
        'points_reward',
        'time_limit_minutes',
        'is_multiplayer',
        'is_family_friendly',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'instructions' => 'array',
            'is_multiplayer' => 'boolean',
            'is_family_friendly' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function gameResults()
    {
        return $this->hasMany(GameResult::class);
    }

    public function gameSessions()
    {
        return $this->hasMany(GameSession::class);
    }

    public function getAverageScore()
    {
        return $this->gameResults()->avg('score');
    }

    public function getTopScore()
    {
        return $this->gameResults()->max('score');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeFamilyFriendly($query)
    {
        return $query->where('is_family_friendly', true);
    }
}

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'educational_game_id',
        'user_id',
        'session_data',
        'start_time',
        'end_time',
        'is_completed',
        'family_members',
    ];

    protected function casts(): array
    {
        return [
            'session_data' => 'array',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_completed' => 'boolean',
            'family_members' => 'array',
        ];
    }

    public function game()
    {
        return $this->belongsTo(EducationalGame::class, 'educational_game_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function results()
    {
        return $this->hasMany(GameResult::class);
    }

    public function getDurationAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return $this->end_time->diffInMinutes($this->start_time);
        }
        return null;
    }
}

class GameResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'educational_game_id',
        'user_id',
        'score',
        'completion_time_minutes',
        'correct_answers',
        'total_questions',
        'difficulty_level',
        'points_earned',
        'achievements_unlocked',
    ];

    protected function casts(): array
    {
        return [
            'achievements_unlocked' => 'array',
        ];
    }

    public function gameSession()
    {
        return $this->belongsTo(GameSession::class);
    }

    public function game()
    {
        return $this->belongsTo(EducationalGame::class, 'educational_game_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAccuracyPercentageAttribute()
    {
        if ($this->total_questions > 0) {
            return ($this->correct_answers / $this->total_questions) * 100;
        }
        return 0;
    }
}