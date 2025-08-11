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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['homework', 'quiz', 'project', 'exam'])->default('homework');
            $table->datetime('due_date')->nullable();
            $table->decimal('max_points', 8, 2)->default(100);
            $table->json('instructions')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('allow_late_submission')->default(true);
            $table->decimal('late_penalty_percent', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['course_id', 'is_published']);
            $table->index(['teacher_id', 'due_date']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->longText('content')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->enum('status', ['draft', 'submitted', 'graded', 'returned'])->default('submitted');
            $table->text('teacher_feedback')->nullable();
            $table->decimal('grade_points', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->decimal('points_earned', 8, 2);
            $table->decimal('max_points', 8, 2);
            $table->decimal('percentage', 5, 2);
            $table->string('letter_grade', 3)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->useCurrent();
            $table->timestamps();

            $table->index(['student_id', 'course_id']);
            $table->index(['course_id', 'assignment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};