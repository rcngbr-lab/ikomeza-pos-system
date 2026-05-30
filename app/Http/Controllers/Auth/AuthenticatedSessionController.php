<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AdminAccountService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        app(AdminAccountService::class)->ensure();

        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            AuditLogService::record([
                'action' => 'LOGIN_FAILED',
                'module' => 'Security',
                'event_type' => 'SECURITY',
                'description' => 'Failed login attempt for username ' . $request->input('username'),
                'metadata' => [
                    'username' => $request->input('username'),
                ],
                'severity' => 'SECURITY',
            ]);

            throw $exception;
        }

        $request->session()->regenerate();

        AuditLogService::record([
            'action' => 'LOGIN_SUCCESS',
            'module' => 'Security',
            'event_type' => 'SECURITY',
            'description' => 'User logged in successfully.',
            'severity' => 'INFO',
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        AuditLogService::record([
            'action' => 'LOGOUT',
            'module' => 'Security',
            'event_type' => 'SECURITY',
            'description' => 'User logged out.',
            'severity' => 'INFO',
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
