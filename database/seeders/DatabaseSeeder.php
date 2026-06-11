<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        User::factory()->create([
            'full_name' => 'مدير المنصة',
            'email' => 'admin@manarat.com',
            'phone_number' => '0500000000',
            'city' => 'الرياض',
            'password' => \Illuminate\Support\Facades\Hash::make('admin1234'),
            'is_admin' => true,
        ]);

        // Student user
        User::factory()->create([
            'full_name' => 'طالب علم تجريبي',
            'email' => 'student@manarat.com',
            'phone_number' => '0511111111',
            'city' => 'المدينة المنورة',
            'password' => \Illuminate\Support\Facades\Hash::make('student1234'),
            'is_admin' => false,
        ]);

        // Seed Courses
        $course1 = \App\Models\Course::create([
            'title' => 'كتاب التوحيد',
            'teacher' => 'الشيخ محمد بن عبد الوهاب',
            'description' => 'شرح مفصل لكتاب التوحيد الذي هو حق الله على العبيد، مع بيان أدلة كل باب من الكتاب والسنة وآثار السلف.',
            'level' => 'مبتدئ',
            'is_active' => true,
        ]);
        
        $course2 = \App\Models\Course::create([
            'title' => 'الأصول الثلاثة',
            'teacher' => 'شرح متن السلف',
            'description' => 'دراسة الأصول الثلاثة التي يجب على كل مسلم ومسلمة معرفتها: معرفة الرب، ومعرفة الدين، ومعرفة النبي ﷺ.',
            'level' => 'مبتدئ',
            'is_active' => true,
        ]);

        $course3 = \App\Models\Course::create([
            'title' => 'العقيدة الواسطية',
            'teacher' => 'شرح المتن',
            'description' => 'متن جامع في عقيدة أهل السنة والجماعة لشيخ الإسلام ابن تيمية رحمه الله.',
            'level' => 'متوسط',
            'is_active' => true,
        ]);

        // Seed Lessons
        for ($i = 1; $i <= 5; $i++) {
            \App\Models\Lesson::create([
                'course_id' => $course1->id,
                'title' => "الدرس {$i}: شرح الباب {$i}",
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_in_seconds' => 1470, // 24:30
                'order_number' => $i,
            ]);
            
            \App\Models\Lesson::create([
                'course_id' => $course2->id,
                'title' => "الدرس {$i}: بيان الأصل {$i}",
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_in_seconds' => 1470,
                'order_number' => $i,
            ]);

            \App\Models\Lesson::create([
                'course_id' => $course3->id,
                'title' => "الدرس {$i}: عقيدة أهل السنة {$i}",
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'duration_in_seconds' => 1470,
                'order_number' => $i,
            ]);
        }
    }
}
