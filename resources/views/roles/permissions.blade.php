@extends('layouts.app')

@section('content')

<div class="max-w-6xl mx-auto p-6">

    <div class="mb-8">

        <h1 class="text-3xl font-black">
            Role Permissions
        </h1>

        <p class="text-slate-500 mt-2">
            {{ $role->name }}
        </p>

    </div>

    <form
        method="POST"
        action="{{ route('roles.permissions.update', $role->id) }}"
    >

        @csrf

        <div class="
            bg-white
            rounded-3xl
            shadow-sm
            border
            p-6
        ">

            <div class="
                grid
                grid-cols-1
                md:grid-cols-2
                lg:grid-cols-3
                gap-4
            ">

                @foreach($permissions as $permission)

                    <label class="
                        flex
                        items-center
                        gap-3
                        border
                        rounded-2xl
                        p-4
                        hover:bg-slate-50
                        cursor-pointer
                    ">

                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->id }}"

                            {{ $role->hasPermissionTo($permission->name)
                                ? 'checked'
                                : ''
                            }}
                        >

                        <span class="font-semibold">
                            {{ $permission->name }}
                        </span>

                    </label>

                @endforeach

            </div>

            <div class="mt-8">

                <button
                    class="
                        bg-black
                        hover:bg-slate-800
                        text-white
                        px-6
                        py-3
                        rounded-2xl
                        font-bold
                    "
                >

                    Save Permissions

                </button>

            </div>

        </div>

    </form>

</div>

@endsection