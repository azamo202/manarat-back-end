<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSetupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Course creation and its relation to Lesson.
     */
    public function test_course_can_be_created_and_has_lessons(): void
    {
        $course = Course::create([
            'title' => 'Introduction to Laravel 12',
            'description' => 'Learn the basics of Laravel 12.',
            'cover_image' => 'images/laravel12.png',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Introduction to Laravel 12',
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Getting Started',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_in_seconds' => 600,
            'order_number' => 1,
        ]);

        $this->assertCount(1, $course->lessons);
        $this->assertEquals($lesson->id, $course->lessons->first()->id);
        $this->assertEquals($course->id, $lesson->course->id);
    }

    /**
     * Test LessonProgress relation to User and Lesson.
     */
    public function test_lesson_progress_belongs_to_user_and_lesson(): void
    {
        $user = User::create([
            'full_name' => 'John Triple Name Doe',
            'phone_number' => '0501234567',
            'city' => 'Riyadh',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $course = Course::create([
            'title' => 'Laravel Advanced',
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Eloquent Deep Dive',
            'youtube_video_id' => 'xyz789',
            'duration_in_seconds' => 1200,
        ]);

        $progress = LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'current_second' => 120,
            'is_completed' => false,
        ]);

        // Verify relationships
        $this->assertEquals($user->id, $progress->user->id);
        $this->assertEquals($lesson->id, $progress->lesson->id);

        $this->assertCount(1, $user->lessonProgress);
        $this->assertEquals($progress->id, $user->lessonProgress->first()->id);

        $this->assertCount(1, $lesson->lessonProgress);
        $this->assertEquals($progress->id, $lesson->lessonProgress->first()->id);

        // Verify User belongsToMany Lesson relationship through lesson_progress
        $this->assertCount(1, $user->lessons);
        $this->assertEquals($lesson->id, $user->lessons->first()->id);
        $this->assertEquals(120, $user->lessons->first()->pivot->current_second);
        $this->assertFalse((bool) $user->lessons->first()->pivot->is_completed);
    }

    /**
     * Test unique compound index constraint on user_id and lesson_id.
     */
    public function test_lesson_progress_unique_user_and_lesson_constraint(): void
    {
        $user = User::create([
            'full_name' => 'Jane Triple Name Doe',
            'phone_number' => '0507654321',
            'city' => 'Jeddah',
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
        ]);

        $course = Course::create([
            'title' => 'Laravel Advanced',
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Eloquent Deep Dive',
            'youtube_video_id' => 'xyz789',
            'duration_in_seconds' => 1200,
        ]);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'current_second' => 50,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // This should trigger a unique constraint violation exception
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'current_second' => 100,
        ]);
    }

    /**
     * Test RegisterUserRequest validation rules.
     */
    public function test_register_user_request_validation(): void
    {
        $request = new \App\Http\Requests\RegisterUserRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('full_name', $rules);
        $this->assertArrayHasKey('phone_number', $rules);
        $this->assertArrayHasKey('city', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);

        // Validate correct inputs
        $validator = \Illuminate\Support\Facades\Validator::make([
            'full_name' => 'John Triple Name Doe',
            'phone_number' => '0599999999',
            'city' => 'Riyadh',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $rules);

        $this->assertTrue($validator->passes());

        // Validate failing inputs (full_name too short, phone too short, password mismatch, invalid email)
        $validatorInvalid = \Illuminate\Support\Facades\Validator::make([
            'full_name' => 'Short', // less than 10
            'phone_number' => '123', // less than 9
            'city' => '', // required
            'email' => 'invalid-email', // not an email
            'password' => 'pass123', // less than 8
            'password_confirmation' => 'pass1234', // not confirmed
        ], $rules);

        $this->assertFalse($validatorInvalid->passes());
        $errors = $validatorInvalid->errors();

        $this->assertTrue($errors->has('full_name'));
        $this->assertTrue($errors->has('phone_number'));
        $this->assertTrue($errors->has('city'));
        $this->assertTrue($errors->has('email'));
        $this->assertTrue($errors->has('password'));
    }
}
