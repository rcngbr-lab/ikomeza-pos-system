@extends('layouts.app')

@section('content')

<div class="form-page">

    {{-- HEADER --}}
    <div class="form-header">

        <div>

            <h1 class="form-title">
                Create Role
            </h1>

            <p class="form-subtitle">
                Configure new system access role and permissions
            </p>

        </div>

        <a href="/roles" class="back-btn">
            ← Back
        </a>

    </div>

    {{-- FORM CARD --}}
    <div class="form-card">

        {{-- ERRORS --}}
        @if ($errors->any())

            <div class="alert-danger">

                <ul>

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

    <form method="POST" action="/roles">

     @csrf

     {{-- CODE --}}
     <div class="form-group">

        <label>
            Role Code
        </label>

        <input
            type="text"
            name="code"
            placeholder="Example: ADMIN"
            value="{{ old('code') }}"
            required
        >

    </div>

    {{-- NAME --}}
    <div class="form-group">

        <label>
            Role Name
        </label>

        <input
            type="text"
            name="name"
            placeholder="Example: Administrator"
            value="{{ old('name') }}"
            required
        >

    </div>

    {{-- DESCRIPTION --}}
    <div class="form-group">

        <label>
            Description
        </label>

        <textarea
            name="description"
            rows="5"
            placeholder="Describe responsibilities and permissions..."
        >{{ old('description') }}</textarea>

    </div>

    {{-- PERMISSIONS --}}
    <div class="form-group">

        <label class="mb-4 block">

            Assign Permissions

        </label>

        <div class="permissions-grid">

            @foreach($permissions as $permission)

                <label class="permission-item">

                    <input
                        type="checkbox"
                        name="permissions[]"
                        value="{{ $permission->name }}"
                    >

                    <span>

                        {{ $permission->name }}

                    </span>

                </label>

            @endforeach

        </div>

      </div>

      {{-- BUTTON --}}
      <button
        type="submit"
        class="submit-btn"
    >

        Save Role

     </button>

    </form>

</div>

@endsection