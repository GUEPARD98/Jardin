<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gamification_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('points');
            $table->string('reason');
            $table->string('activity_type'); // assignment_completion, game_completion, forum_participation, etc.
            $table->unsignedBigInteger('activity_id')->nullable(); // ID of the related activity
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'activity_type']);
            $table->index('expires_at');
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('color', 7)->default('#F59E0B');
            $table->integer('points_required')->default(0);
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('badge_id')->constrained('badges')->onDelete('cascade');
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'badge_id']);
        });

        Schema::create('educational_games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // math, science, language, etc.
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
            $table->enum('game_type', ['quiz', 'puzzle', 'simulation', 'strategy', 'memory']);
            $table->json('config'); // Game-specific configuration
            $table->string('thumbnail')->nullable();
            $table->json('instructions')->nullable();
            $table->integer('points_reward')->default(10);
            $table->integer('time_limit_minutes')->nullable();
            $table->boolean('is_multiplayer')->default(false);
            $table->boolean('is_family_friendly')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'level']);
            $table->index(['is_active', 'is_family_friendly']);
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educational_game_id')->constrained('educational_games')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('session_data')->nullable(); // Game state, progress, etc.
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->json('family_members')->nullable(); // For family-friendly games
            $table->timestamps();

            $table->index(['user_id', 'educational_game_id']);
            $table->index('start_time');
        });

        Schema::create('game_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->onDelete('cascade');
            $table->foreignId('educational_game_id')->constrained('educational_games')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('score')->default(0);
            $table->integer('completion_time_minutes')->nullable();
            $table->integer('correct_answers')->default(0);
            $table->integer('total_questions')->default(0);
            $table->string('difficulty_level')->nullable();
            $table->integer('points_earned')->default(0);
            $table->json('achievements_unlocked')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'educational_game_id']);
            $table->index('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_results');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('educational_games');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('gamification_points');
    }
};