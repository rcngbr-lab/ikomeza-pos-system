@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <h2 class="mb-4">
                Edit Permission
            </h2>

            <form
                action="{{ route('permissions.update', $permission->id) }}"
                method="POST"
            >

                @csrf
                @method('PUT')

                <div class="mb-3">

                    <label class="form-label">
                        Permission Code
                    </label>

                    <input
                        type="text"
                        name="code"
                        value="{{ $permission->code }}"
                        class="form-control"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Permission Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ $permission->name }}"
                        class="form-control"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Permission
                </button>

            </form>

        </div>

    </div>

</div>

@endsection
