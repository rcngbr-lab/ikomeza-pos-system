<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'FRONTIER POS') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-slate-100 text-slate-900 antialiased"
    x-data="{
        isPosRoute: @js(request()->routeIs('pos.*')),
        posKiosk: false,
        sidebarCollapsed: false,
        init() {
            const saved = localStorage.getItem('frontier.sidebar');
            this.sidebarCollapsed = saved
                ? saved === 'collapsed'
                : (@js(request()->routeIs('pos.*')) && window.matchMedia('(max-width: 1280px)').matches);

            this.posKiosk = this.isPosRoute
                && localStorage.getItem('frontier.pos.kiosk') === 'enabled'
                && Boolean(document.fullscreenElement);

            if (this.isPosRoute && !document.fullscreenElement) {
                localStorage.setItem('frontier.pos.kiosk', 'disabled');
            }

            document.addEventListener('fullscreenchange', () => {
                if (this.isPosRoute && !document.fullscreenElement && this.posKiosk) {
                    this.posKiosk = false;
                    localStorage.setItem('frontier.pos.kiosk', 'disabled');
                }

                if (this.isPosRoute && document.fullscreenElement) {
                    this.posKiosk = true;
                    localStorage.setItem('frontier.pos.kiosk', 'enabled');
                }

                this.$nextTick(() => window.dispatchEvent(new Event('resize')));
            });
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('frontier.sidebar', this.sidebarCollapsed ? 'collapsed' : 'expanded');
        },
        async togglePosKiosk() {
            if (!this.isPosRoute) {
                return;
            }

            try {
                if (!this.posKiosk && !document.fullscreenElement) {
                    await document.documentElement.requestFullscreen();
                    this.posKiosk = true;
                    localStorage.setItem('frontier.pos.kiosk', 'enabled');
                    this.$nextTick(() => window.dispatchEvent(new Event('resize')));
                    return;
                }

                if (this.posKiosk && document.fullscreenElement) {
                    await document.exitFullscreen();
                    return;
                }
            } catch (error) {
                this.posKiosk = false;
                localStorage.setItem('frontier.pos.kiosk', 'disabled');
            }

            this.posKiosk = false;
            localStorage.setItem('frontier.pos.kiosk', 'disabled');
            this.$nextTick(() => window.dispatchEvent(new Event('resize')));
        }
    }"
    :class="{ 'pos-kiosk-active': isPosRoute && posKiosk }"
>
    <div class="flex min-h-screen">
        <aside
            class="fixed inset-y-0 z-40 hidden flex-col border-r border-slate-800 bg-slate-950 transition-[width] duration-200 lg:flex"
            :class="sidebarCollapsed ? 'w-20' : 'w-64'"
            x-show="!(isPosRoute && posKiosk)"
        >
            @include('components.sidebar')
        </aside>

        <div
            class="flex min-w-0 flex-1 flex-col transition-[padding] duration-200"
            :class="isPosRoute && posKiosk ? 'lg:pl-0' : (sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64')"
        >
            <div x-show="!(isPosRoute && posKiosk)">
                @include('components.topbar')
            </div>

            <main
                class="flex-1 overflow-y-auto"
                :class="isPosRoute && posKiosk ? 'h-screen pb-0' : 'pb-24 lg:pb-0'"
            >
                <div :class="isPosRoute && posKiosk ? 'h-full p-0' : 'p-2.5 lg:p-4'">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <div class="lg:hidden" x-show="!(isPosRoute && posKiosk)">
        @include('components.mobile-bottom-nav')
    </div>
</body>
</html>
