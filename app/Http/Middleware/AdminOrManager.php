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
            || !$user->hasOperationalRole(
                'ADMIN',
                'MANAGER',
                'ADMINISTRATOR',
                'KITCHEN_MANAGER',
                'KITCHEN_CHIEF',
                'BAR_MANAGER',
                'BAR_CHIEF',
                'BARTENDER'
            )
        ) {

            abort(403);
        }

        return $next($request);
    }
}
