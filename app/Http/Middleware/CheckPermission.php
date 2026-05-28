<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        $permission
    )
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | BASIC ROLE CHECK
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | TEMPORARY ADMIN BYPASS
        |--------------------------------------------------------------------------
        */

        if (
            $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
        ) {

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | CASHIER RESTRICTIONS
        |--------------------------------------------------------------------------
        */

        if (
            $permission === 'CREATE_PRODUCT'
        ) {

            abort(
                403,
                'Unauthorized'
            );
        }

        return $next($request);
    }
}
