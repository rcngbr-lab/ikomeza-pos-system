<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Schema;

return Application::configure(
    basePath: dirname(__DIR__)
)

->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
->withMiddleware(function (Middleware $middleware): void {

    $middleware->trustProxies(at: '*');

    $middleware->alias([

        'role' =>
            \Spatie\Permission\Middleware\RoleMiddleware::class,

        'operational.role' =>
            \App\Http\Middleware\RoleMiddleware::class,

        'permission' =>
            \Spatie\Permission\Middleware\PermissionMiddleware::class,

        'role_or_permission' =>
            \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

        'admin.manager' =>
            \App\Http\Middleware\AdminOrManager::class,

    ]);

})


->withExceptions(function (Exceptions $exceptions): void {

    $exceptions->report(function (Throwable $exception) {
        try {
            if (Schema::hasTable('error_events')) {
                \App\Models\ErrorEvent::create([
                    'user_id' => auth()->id(),
                    'branch_id' => auth()->user()?->branch_id,
                    'source' => 'APPLICATION',
                    'severity' => 'ERROR',
                    'message' => str($exception->getMessage())->limit(500)->toString(),
                    'context' => json_encode([
                        'class' => $exception::class,
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'url' => request()?->fullUrl(),
                    ], JSON_UNESCAPED_SLASHES),
                    'status' => 'OPEN',
                ]);
            }
        } catch (Throwable) {
            //
        }
    });

})

->create();
