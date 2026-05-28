@extends('layouts.app')

@section('content')

<div class="max-w-4xl mx-auto p-4 md:p-6">

    <div class="bg-white rounded-2xl shadow-sm p-6">

        <div class="mb-6">

            <h1 class="text-2xl font-bold text-gray-800">
                Edit User
            </h1>

        </div>

        @if ($errors->any())

            <div class="mb-5 rounded-xl bg-red-100 p-4 text-red-700">

                <ul class="space-y-1">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif

        <form
            action="{{ route('users.update', $user) }}"
            method="POST"
        >
            @csrf

            @method('PUT')

            @include('users.partials.form')

        </form>

    </div>

</div>

@endsection