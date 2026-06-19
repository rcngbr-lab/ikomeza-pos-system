<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>

        FRONTIER POS

    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>

<body class="bg-slate-100 text-slate-900">

    <div class="min-h-screen pb-24">

        @include('components.topbar')

        <main>

            @yield('content')

        </main>

    </div>

    @include('components.mobile-bottom-nav')

</body>

</html>