<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- NAME --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Full Name
        </label>

        <input
            type="text"
            name="name"
            value="{{ old('name', $user->name ?? '') }}"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
        >
    </div>

    {{-- EMAIL --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Email
        </label>

        <input
            type="email"
            name="email"
            value="{{ old('email', $user->email ?? '') }}"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
        >
    </div>

    {{-- PHONE --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Phone
        </label>

        <input
            type="text"
            name="phone"
            value="{{ old('phone', $user->phone ?? '') }}"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
        >
    </div>

    {{-- PASSWORD --}}
    @if(!isset($user))
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Password
        </label>

        <input
            type="password"
            name="password"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
        >
    </div>
    @endif

    {{-- BRANCH --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Branch
        </label>

        <select
            name="branch_id"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
        >
            <option value="">
                Select Branch
            </option>

            @foreach($branches as $branch)

                <option
                    value="{{ $branch->id }}"
                    @selected(
                        old(
                            'branch_id',
                            $user->branch_id ?? ''
                        ) == $branch->id
                    )
                >
                    {{ $branch->name }}
                </option>

            @endforeach
        </select>
    </div>

    {{-- ROLE --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Role
        </label>

        <select
            name="role"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
        >
            <option value="">
                Select Role
            </option>

            @foreach($roles as $role)

                <option
                    value="{{ $role->name }}"
                    @selected(
                        old(
                            'role',
                            isset($user)
                                ? $user->roles->first()?->name
                                : ''
                        ) == $role->name
                    )
                >
                    {{ $role->name }}
                </option>

            @endforeach
        </select>
    </div>

    {{-- STATUS --}}
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">
            Status
        </label>

        <select
            name="status"
            class="w-full h-12 px-4 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            required
        >
            <option value="ACTIVE">
                ACTIVE
            </option>

            <option value="INACTIVE">
                INACTIVE
            </option>

            <option value="SUSPENDED">
                SUSPENDED
            </option>
        </select>
    </div>

</div>

<div class="mt-8">

    <button
        type="submit"
        class="w-full md:w-auto min-h-[48px] px-6 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition"
    >
        Save User
    </button>

</div>