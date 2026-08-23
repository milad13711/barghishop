<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // مهمان‌های بخش مدیریت به لاگین ادمین می‌روند، بقیه به لاگین مشتری
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
