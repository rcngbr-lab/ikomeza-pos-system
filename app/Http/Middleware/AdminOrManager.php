<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrManager
{
    public function handle(
        Request $request,
        Closure $next
    ) {

        $user = auth()->user();

        if (
            !$user
            || !$user->hasOperationalRole('ADMIN', 'MANAGER', 'ADMINISTRATOR')
        ) {

            abort(403);
        }

        return $next($request);
    }
}
