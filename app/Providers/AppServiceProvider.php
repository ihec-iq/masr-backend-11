<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Policies
        $this->registerPolicies();
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Employee::class, \App\Policies\EmployeePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Vacation::class, \App\Policies\VacationPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Archive::class, \App\Policies\ArchivePolicy::class);
        
        // Dashboard policy for non-model authorization
        \Illuminate\Support\Facades\Gate::define('viewDashboard', [\App\Policies\DashboardPolicy::class, 'viewDashboard']);
    }

    /**
     * Bootstrap any application services.
     */ 
    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        $this->configureRateLimiting();

        // سياسة كلمة المرور الموحّدة لكل نماذج الطلبات (إنشاء مستخدم/تغيير كلمة المرور)
        \Illuminate\Validation\Rules\Password::defaults(
            fn() => \Illuminate\Validation\Rules\Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
        );
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.'
                    ], 429);
                });
        });

        \Illuminate\Support\Facades\RateLimiter::for('auth', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again later.'
                    ], 429);
                });
        });

        \Illuminate\Support\Facades\RateLimiter::for('backup', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perHour(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many backup requests. Please try again later.'
                    ], 429);
                });
        });

        // Telegram يرسل التحديثات من مجموعة IP صغيرة وبمعدل عالٍ، فلا يصلح حدّ الـ api
        // العام (60/دقيقة لكل IP) لأن 429 يجعل Telegram يتراجع ثم يُسقط التحديثات.
        \Illuminate\Support\Facades\RateLimiter::for('telegram', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('reports', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
