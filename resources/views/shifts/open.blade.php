@extends('layouts.app')

@section('content')

<div class="p-6">

    <div class="max-w-xl mx-auto">

        <div class="bg-white rounded-2xl shadow-sm p-8">

            <div class="mb-8">

                <h1 class="text-3xl font-bold text-slate-900">

                    Open Shift

                </h1>

                <p class="text-slate-500 mt-2">

                    Start cashier session

                </p>

            </div>

            @if(session('error'))

                <div class="mb-4 bg-red-100 text-red-700
                            px-4 py-3 rounded-xl">

                    {{ session('error') }}

                </div>

            @endif

            <form
                action="{{ route('shifts.open') }}"
                method="POST"
            >

                @csrf

                <div class="mb-6">

                    <label class="block text-sm font-semibold
                                   text-slate-700 mb-2">

                        Opening Cash

                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="opening_cash"
                        required
                        autofocus
                        placeholder="Enter opening cash"
                        class="w-full border border-slate-300
                               rounded-xl px-4 py-4
                               focus:ring-2 focus:ring-green-500
                               focus:border-green-500"
                    >

                    @error('opening_cash')

                        <p class="text-red-600 text-sm mt-2">

                            {{ $message }}

                        </p>

                    @enderror

                </div>

                <button
                    type="submit"
                    class="w-full bg-green-600 hover:bg-green-700
                           text-white font-bold
                           py-4 rounded-xl
                           transition"
                >

                    OPEN SHIFT

                </button>

            </form>

        </div>

    </div>

</div>

@endsection
