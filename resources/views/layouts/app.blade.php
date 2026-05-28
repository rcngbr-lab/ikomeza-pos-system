<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IKOMEZA POS') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="fixed inset-y-0 z-40 hidden w-64 flex-col border-r border-slate-800 bg-slate-950 lg:flex">
            @include('components.sidebar')
        </aside>

        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
            @include('components.topbar')

            <main class="flex-1 overflow-y-auto pb-24 lg:pb-0">
                <div class="p-4 lg:p-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <div class="lg:hidden">
        @include('components.mobile-bottom-nav')
    </div>
</body>
</html>
