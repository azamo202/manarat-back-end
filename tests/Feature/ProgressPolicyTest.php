<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProgressPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the ProgressPolicy logic and registration.
     */
    public function test_progress_policy_authorization(): void
    {
        $userA = User::create([
            'full_name' => 'User Alice Name',
            'phone_number' => '0501112224',
            'city' => 'Riyadh',
            'email' => 'alice@example.com',
            'password' => bcrypt('password123'),
        ]);

        $userB = User::create([
            'full_name' => 'User Bob Name',
            'phone_number' => '0501112225',
            'city' => 'Jeddah',
            'email' => 'bob@example.com',
            'password' => bcrypt('password123'),
        ]);

        $course = Course::create([
            'title' => 'Main Course',
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Specific Lesson',
            'youtube_video_id' => 'yt123',
            'duration_in_seconds' => 100,
        ]);

        // Alice creates progress
        $progressAlice = LessonProgress::create([
            'user_id' => $userA->id,
            'lesson_id' => $lesson->id,
            'current_second' => 50,
        ]);

        // 1. Test registration through the Gate facade
        $this->assertTrue(Gate::forUser($userA)->allows('update', $progressAlice));
        $this->assertFalse(Gate::forUser($userB)->allows('update', $progressAlice));

        // 2. Test user model helper methods
        $this->assertTrue($userA->can('update', $progressAlice));
        $this->assertFalse($userB->can('update', $progressAlice));
    }
}
