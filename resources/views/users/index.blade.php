@extends('layouts.app')

@section('content')

<div class="p-4 md:p-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

        <div>

            <h1 class="text-2xl font-bold text-gray-800">
                Users
            </h1>

            <p class="text-sm text-gray-500">
                Manage staff accounts
            </p>

        </div>

        <a
            href="{{ route('users.create') }}"
            class="inline-flex items-center justify-center min-h-[48px] px-5 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition"
        >
            + Create User
        </a>

    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[800px]">

                <thead class="bg-gray-100">

                    <tr>

                        <th class="px-4 py-4 text-left text-sm font-semibold">
                            Name
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-semibold">
                            Username
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-semibold">
                            Role
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-semibold">
                            Department
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-semibold">
                            Branch
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-semibold">
                            Status
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-semibold">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($users as $user)

                        <tr class="border-t">

                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">
                                    {{ $user->name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ !str_ends_with(strtolower((string) $user->email), '@ikomeza.local') ? $user->email : 'No contact email' }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <span class="px-3 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">
                                    {{ $user->username ?? '-' }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                    {{ $user->roles->first()?->name ?? $user->roleLabel() }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                                    {{ $user->department->name ?? 'All' }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                                    {{ $user->branch->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td class="px-4 py-4">

                                @if($user->status === 'ACTIVE')

                                    <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                        ACTIVE
                                    </span>

                                @elseif($user->status === 'INACTIVE')

                                    <span class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">
                                        INACTIVE
                                    </span>

                                @else

                                    <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-700">
                                        SUSPENDED
                                    </span>

                                @endif

                            </td>

                            <td class="px-4 py-4">

                                <a
                                    href="{{ route('users.edit', $user) }}"
                                    class="inline-flex items-center justify-center min-h-[40px] px-4 bg-gray-900 text-white rounded-lg text-sm"
                                >
                                    Edit
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-gray-500"
                            >
                                No users found
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <div class="mt-6">

        {{ $users->links() }}

    </div>

</div>

@endsection
