<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IKOMEZA POS') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <style>
            body {
                margin: 0;
                min-height: 100vh;
                background: #f1f5f9;
                color: #0f172a;
                font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            .auth-shell {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }

            .auth-panel {
                width: 100%;
                max-width: 440px;
            }

            .auth-brand {
                margin-bottom: 22px;
                text-align: center;
            }

            .auth-mark {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 72px;
                height: 72px;
                border-radius: 18px;
                background: #4f46e5;
                color: #fff;
                font-size: 22px;
                font-weight: 900;
                letter-spacing: 0;
                box-shadow: 0 18px 40px rgba(79, 70, 229, 0.25);
            }

            .auth-title {
                margin: 14px 0 0;
                font-size: 26px;
                font-weight: 900;
                letter-spacing: 0;
            }

            .auth-subtitle {
                margin: 6px 0 0;
                color: #64748b;
                font-size: 14px;
                font-weight: 600;
            }

            .auth-card {
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                background: #fff;
                padding: 28px;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            }

            .auth-card label {
                display: block;
                margin-bottom: 7px;
                color: #334155;
                font-size: 13px;
                font-weight: 800;
            }

            .auth-card input[type="email"],
            .auth-card input[type="password"],
            .auth-card input[type="text"] {
                width: 100%;
                min-height: 46px;
                box-sizing: border-box;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                background: #f8fafc;
                padding: 0 14px;
                color: #0f172a;
                font-size: 15px;
                outline: none;
            }

            .auth-card input:focus {
                border-color: #4f46e5;
                background: #fff;
                box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
            }

            .auth-card button[type="submit"] {
                min-height: 44px;
                border: 0;
                border-radius: 12px;
                background: #4f46e5;
                padding: 0 18px;
                color: #fff;
                font-size: 14px;
                font-weight: 900;
                cursor: pointer;
            }

            .auth-card button[type="submit"]:hover {
                background: #4338ca;
            }

            .auth-card a {
                color: #4f46e5;
                font-weight: 800;
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="auth-shell">
            <section class="auth-panel">
                <div class="auth-brand">
                    <a href="/" class="auth-mark" aria-label="IKOMEZA POS">
                        POS
                    </a>
                    <h1 class="auth-title">
                        IKOMEZA POS
                    </h1>
                    <p class="auth-subtitle">
                        Secure business operations access
                    </p>
                </div>

                <div class="auth-card">
                    {{ $slot }}
                </div>
            </section>
        </main>
    </body>
</html>
