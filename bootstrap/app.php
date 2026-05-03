<?php

use App\Http\Middleware\CheckSubscriptionLimit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // 1️⃣ هنا نقوم بتعريف الـ Middleware الخاص بنا بلقب (Alias)
        $middleware->alias([
            'verified.landlord' => \App\Http\Middleware\EnsureLandlordVerified::class,
            'admin.check'      => \App\Http\Middleware\AdminCheck::class,
            'check.subscription.limit' => CheckSubscriptionLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
