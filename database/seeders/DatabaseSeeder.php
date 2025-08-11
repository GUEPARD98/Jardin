<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Badge;
use App\Models\EducationalGame;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@jardin.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
        ]);

        // Create teachers
        $teacher1 = User::create([
            'name' => 'María García',
            'email' => 'maria.garcia@jardin.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
            'phone' => '+1234567890',
            'date_of_birth' => '1985-03-15',
        ]);

        $teacher2 = User::create([
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos.rodriguez@jardin.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
            'phone' => '+1234567891',
            'date_of_birth' => '1980-07-22',
        ]);

        // Create parents
        $parent1 = User::create([
            'name' => 'Ana López',
            'email' => 'ana.lopez@example.com',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
            'phone' => '+1234567892',
            'date_of_birth' => '1982-11-08',
        ]);

        $parent2 = User::create([
            'name' => 'Luis Martínez',
            'email' => 'luis.martinez@example.com',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
            'phone' => '+1234567893',
            'date_of_birth' => '1978-05-14',
        ]);

        // Create students
        $student1 = User::create([
            'name' => 'Sofía López',
            'email' => 'sofia.lopez@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'parent_id' => $parent1->id,
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
            'date_of_birth' => '2010-08-20',
        ]);

        $student2 = User::create([
            'name' => 'Diego Martínez',
            'email' => 'diego.martinez@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'parent_id' => $parent2->id,
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
            'date_of_birth' => '2012-02-18',
        ]);

        $student3 = User::create([
            'name' => 'Emma Wilson',
            'email' => 'emma.wilson@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'is_active' => true,
            'profile_completed' => true,
            'email_verified_at' => now(),
            'date_of_birth' => '2011-06-10',
        ]);

        // Create course categories
        $mathCategory = CourseCategory::create([
            'name' => 'Matemáticas',
            'description' => 'Cursos de matemáticas para todos los niveles',
            'icon' => 'calculator',
            'color' => '#3B82F6',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $scienceCategory = CourseCategory::create([
            'name' => 'Ciencias',
            'description' => 'Cursos de ciencias naturales y experimentales',
            'icon' => 'flask',
            'color' => '#10B981',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $languageCategory = CourseCategory::create([
            'name' => 'Idiomas',
            'description' => 'Cursos de idiomas y comunicación',
            'icon' => 'language',
            'color' => '#F59E0B',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create courses
        $mathCourse = Course::create([
            'title' => 'Matemáticas Básicas',
            'description' => 'Curso introductorio de matemáticas para estudiantes de primaria',
            'teacher_id' => $teacher1->id,
            'category_id' => $mathCategory->id,
            'level' => 'beginner',
            'duration_hours' => 40,
            'price' => 99.99,
            'status' => 'active',
            'max_students' => 25,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(67),
            'is_featured' => true,
            'prerequisites' => ['Conocimientos básicos de números'],
            'learning_objectives' => [
                'Operaciones básicas de suma y resta',
                'Multiplicación y división',
                'Fracciones simples',
                'Resolución de problemas'
            ],
        ]);

        $scienceCourse = Course::create([
            'title' => 'Ciencias Naturales',
            'description' => 'Exploración del mundo natural y sus fenómenos',
            'teacher_id' => $teacher2->id,
            'category_id' => $scienceCategory->id,
            'level' => 'beginner',
            'duration_hours' => 35,
            'price' => 89.99,
            'status' => 'active',
            'max_students' => 20,
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(74),
            'is_featured' => true,
            'prerequisites' => ['Curiosidad por la naturaleza'],
            'learning_objectives' => [
                'Comprensión del método científico',
                'Conocimiento de ecosistemas',
                'Experimentos básicos',
                'Observación y registro'
            ],
        ]);

        $languageCourse = Course::create([
            'title' => 'Inglés para Niños',
            'description' => 'Curso de inglés básico diseñado para niños',
            'teacher_id' => $teacher1->id,
            'category_id' => $languageCategory->id,
            'level' => 'beginner',
            'duration_hours' => 30,
            'price' => 79.99,
            'status' => 'active',
            'max_students' => 15,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(70),
            'prerequisites' => ['Ninguno'],
            'learning_objectives' => [
                'Vocabulario básico',
                'Frases de saludo',
                'Números y colores',
                'Conversación simple'
            ],
        ]);

        // Create enrollments
        Enrollment::create([
            'student_id' => $student1->id,
            'course_id' => $mathCourse->id,
            'enrolled_at' => now(),
            'status' => 'active',
            'payment_status' => 'paid',
            'progress' => 25.5,
        ]);

        Enrollment::create([
            'student_id' => $student1->id,
            'course_id' => $languageCourse->id,
            'enrolled_at' => now()->subDays(5),
            'status' => 'active',
            'payment_status' => 'paid',
            'progress' => 40.0,
        ]);

        Enrollment::create([
            'student_id' => $student2->id,
            'course_id' => $scienceCourse->id,
            'enrolled_at' => now()->subDays(3),
            'status' => 'active',
            'payment_status' => 'paid',
            'progress' => 15.0,
        ]);

        Enrollment::create([
            'student_id' => $student3->id,
            'course_id' => $mathCourse->id,
            'enrolled_at' => now()->subDays(7),
            'status' => 'active',
            'payment_status' => 'paid',
            'progress' => 60.0,
        ]);

        // Create assignments
        Assignment::create([
            'title' => 'Suma y Resta Básica',
            'description' => 'Ejercicios de suma y resta con números de dos dígitos',
            'course_id' => $mathCourse->id,
            'teacher_id' => $teacher1->id,
            'type' => 'homework',
            'due_date' => now()->addDays(14),
            'max_points' => 100,
            'instructions' => ['Resuelve todos los ejercicios', 'Muestra tu trabajo', 'Revisa tus respuestas'],
            'is_published' => true,
            'allow_late_submission' => true,
            'late_penalty_percent' => 10,
        ]);

        Assignment::create([
            'title' => 'Experimento: Plantas',
            'description' => 'Observa el crecimiento de una planta durante una semana',
            'course_id' => $scienceCourse->id,
            'teacher_id' => $teacher2->id,
            'type' => 'project',
            'due_date' => now()->addDays(21),
            'max_points' => 150,
            'instructions' => [
                'Planta una semilla',
                'Registra cambios diarios',
                'Toma fotos del progreso',
                'Escribe tus observaciones'
            ],
            'is_published' => true,
            'allow_late_submission' => false,
        ]);

        Assignment::create([
            'title' => 'Vocabulario en Inglés',
            'description' => 'Quiz de 20 palabras en inglés',
            'course_id' => $languageCourse->id,
            'teacher_id' => $teacher1->id,
            'type' => 'quiz',
            'due_date' => now()->addDays(7),
            'max_points' => 80,
            'instructions' => ['Estudia la lista de vocabulario', 'El quiz será de opción múltiple'],
            'is_published' => true,
            'allow_late_submission' => true,
            'late_penalty_percent' => 5,
        ]);

        // Create badges
        Badge::create([
            'name' => 'Primer Paso',
            'description' => 'Completaste tu primera tarea',
            'icon' => 'star',
            'color' => '#F59E0B',
            'points_required' => 10,
            'category' => 'achievement',
            'is_active' => true,
        ]);

        Badge::create([
            'name' => 'Matemático Principiante',
            'description' => 'Completaste 5 ejercicios de matemáticas',
            'icon' => 'calculator',
            'color' => '#3B82F6',
            'points_required' => 50,
            'category' => 'subject',
            'is_active' => true,
        ]);

        Badge::create([
            'name' => 'Científico Curioso',
            'description' => 'Participaste en 3 experimentos',
            'icon' => 'flask',
            'color' => '#10B981',
            'points_required' => 75,
            'category' => 'subject',
            'is_active' => true,
        ]);

        // Create educational games
        EducationalGame::create([
            'title' => 'Suma Rápida',
            'description' => 'Juego de suma contra el tiempo',
            'category' => 'math',
            'level' => 'beginner',
            'course_id' => $mathCourse->id,
            'game_type' => 'quiz',
            'config' => [
                'max_number' => 20,
                'operations' => ['addition'],
                'time_per_question' => 30,
                'total_questions' => 10
            ],
            'instructions' => ['Resuelve las sumas lo más rápido posible', 'Tienes 30 segundos por pregunta'],
            'points_reward' => 10,
            'time_limit_minutes' => 10,
            'is_multiplayer' => false,
            'is_family_friendly' => true,
            'is_active' => true,
        ]);

        EducationalGame::create([
            'title' => 'Explorador de Animales',
            'description' => 'Descubre datos fascinantes sobre animales',
            'category' => 'science',
            'level' => 'beginner',
            'course_id' => $scienceCourse->id,
            'game_type' => 'quiz',
            'config' => [
                'topics' => ['mammals', 'birds', 'reptiles'],
                'difficulty' => 'easy',
                'show_hints' => true
            ],
            'instructions' => ['Responde preguntas sobre animales', 'Aprende datos curiosos'],
            'points_reward' => 15,
            'time_limit_minutes' => 15,
            'is_multiplayer' => true,
            'is_family_friendly' => true,
            'is_active' => true,
        ]);

        EducationalGame::create([
            'title' => 'Palabras en Inglés',
            'description' => 'Juego de memoria con vocabulario en inglés',
            'category' => 'language',
            'level' => 'beginner',
            'course_id' => $languageCourse->id,
            'game_type' => 'memory',
            'config' => [
                'word_categories' => ['colors', 'numbers', 'family'],
                'cards_count' => 16,
                'show_pronunciation' => true
            ],
            'instructions' => ['Encuentra las parejas de palabras', 'Escucha la pronunciación'],
            'points_reward' => 12,
            'time_limit_minutes' => 12,
            'is_multiplayer' => false,
            'is_family_friendly' => true,
            'is_active' => true,
        ]);

        $this->command->info('Database seeded successfully with sample academic data!');
    }
}