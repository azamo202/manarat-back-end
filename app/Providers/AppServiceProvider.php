<?php

namespace App\Providers;

use App\Models\LessonProgress;
use App\Policies\ProgressPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(LessonProgress::class, ProgressPolicy::class);
    }
}
