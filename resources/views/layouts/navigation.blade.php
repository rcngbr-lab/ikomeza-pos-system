<nav class="bg-white border-b border-gray-100">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between h-16 items-center">

            <!-- LEFT -->
            <div class="flex items-center gap-6">

                <!-- LOGO -->
                <a href="{{ route('dashboard') }}">

                    <x-application-logo
                        class="block h-9 w-auto fill-current text-gray-800"
                    />

                </a>

                <!-- NAV LINKS -->
                <div class="flex items-center gap-4">

                    <a
                        href="{{ route('dashboard') }}"
                        class="text-sm font-semibold text-gray-700 hover:text-indigo-600"
                    >
                        Dashboard
                    </a>

                    <a
                        href="{{ url('/pos') }}"
                        class="text-sm font-semibold text-gray-700 hover:text-indigo-600"
                    >
                        POS Terminal
                    </a>

                    <a
                        href="{{ route('inventory.index') }}"
                        class="text-sm font-semibold text-gray-700 hover:text-indigo-600"
                    >
                        Inventory
                    </a>

                    <a
                        href="{{ url('/reports') }}"
                        class="text-sm font-semibold text-gray-700 hover:text-indigo-600"
                    >
                        Reports
                    </a>

                    <a
                        href="{{ url('/products') }}"
                        class="text-sm font-semibold text-gray-700 hover:text-indigo-600"
                    >
                        Products
                    </a>

                </div>

            </div>

            <!-- RIGHT -->
            <div class="flex items-center gap-4">

                <span class="text-sm text-gray-700 font-semibold">

                    {{ Auth::user()->name }}

                </span>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="
                            bg-red-500
                            hover:bg-red-600
                            text-white
                            text-sm
                            px-4
                            py-2
                            rounded-lg
                            transition
                        "
                    >

                        Logout

                    </button>

                </form>

            </div>

        </div>

    </div>

</nav>