<?php

namespace App\Providers;

use App\Application\Quiz\Repositories\AttemptRepository;
use App\Application\Quiz\Repositories\AttemptRepositoryInterface;
use App\Application\Quiz\Repositories\QuizRepository;
use App\Application\Quiz\Repositories\QuizRepositoryInterface;
use App\Domain\Quiz\Events\CertificateEligible;
use App\Domain\Quiz\Events\QuizAttemptStarted;
use App\Domain\Quiz\Events\QuizAttemptSubmitted;
use App\Domain\Quiz\Events\QuizFailed;
use App\Domain\Quiz\Events\QuizPassed;
use App\Domain\Quiz\Listeners\NotifyStudentQuizFailed;
use App\Domain\Quiz\Listeners\NotifyStudentQuizPassed;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Policies\ProgressPolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuizPolicy;
use App\Policies\QuestionPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(QuizRepositoryInterface::class, QuizRepository::class);
        $this->app->bind(AttemptRepositoryInterface::class, AttemptRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policies
        Gate::policy(LessonProgress::class, ProgressPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);

        // Quiz Event → Listener bindings
        Event::listen(QuizPassed::class, NotifyStudentQuizPassed::class);
        Event::listen(QuizFailed::class, NotifyStudentQuizFailed::class);

        // Certificate eligibility — event-driven, attach listeners here when ready
        // Event::listen(CertificateEligible::class, GenerateCertificate::class);
    }
}
