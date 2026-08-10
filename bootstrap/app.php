<?php

use App\Http\Middleware\LocalizationMiddleware;
use App\Http\Middleware\MaintenanceCheckerMiddleware;
use App\Http\Middleware\VerifyTelegramWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Middleware\Authenticate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
                            'locale' => LocalizationMiddleware::class,
                            'maintenance' => MaintenanceCheckerMiddleware::class,
                            'telegram.webhook' => VerifyTelegramWebhook::class,
                            'auth' => Authenticate::class,
                            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
                            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
                            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
                        ]);

        // Rate Limiting Configuration
        $middleware->throttleApi();
        
        // $middleware->append(CorsMiddleware::class); // Register Cors middleware

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
