@extends('layouts.app')

@section('content')

<style>

    .page-wrapper{
        padding: 30px;
    }

    .page-title{
        font-size: 56px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 10px;
    }

    .page-subtitle{
        font-size: 22px;
        color: #64748b;
        margin-bottom: 40px;
    }

    .form-card{
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        max-width: 1000px;
    }

    .form-grid{
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .form-group{
        display: flex;
        flex-direction: column;
    }

    .form-group.full{
        grid-column: span 2;
    }

    .form-label{
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 10px;
        color: #0f172a;
    }

    .form-input,
    .form-textarea{
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 18px;
        font-size: 16px;
        outline: none;
        transition: 0.2s;
    }

    .form-input:focus,
    .form-textarea:focus{
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,0.15);
    }

    .form-textarea{
        min-height: 180px;
        resize: vertical;
    }

    .btn-primary{
        margin-top: 30px;
        background: #2563eb;
        color: white;
        border: none;
        padding: 16px 30px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-primary:hover{
        background: #1d4ed8;
    }

    .error-box{
        background: #fee2e2;
        color: #991b1b;
        padding: 14px;
        border-radius: 10px;
        margin-bottom: 25px;
    }

</style>

<div class="page-wrapper">

    <div class="page-title">

        Edit Role

    </div>

    <div class="page-subtitle">

        Modify system role configuration and responsibilities

    </div>

    <div class="form-card">

        @if ($errors->any())

            <div class="error-box">

                <ul>

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

        <form
            method="POST"
            action="{{ route('roles.update', $role->id) }}"
        >

            @csrf
            @method('PUT')

            <div class="form-grid">

                <div class="form-group">

                    <label class="form-label">

                        Role Code

                    </label>

                    <input
                        type="text"
                        name="code"
                        class="form-input"
                        value="{{ old('code', $role->code) }}"
                        required
                    >

                </div>

                <div class="form-group">

                    <label class="form-label">

                        Role Name

                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-input"
                        value="{{ old('name', $role->name) }}"
                        required
                    >

                </div>

                <div class="form-group full">

                    <label class="form-label">

                        Description

                    </label>

                    <textarea
                        name="description"
                        class="form-textarea"
                    >{{ old('description', $role->description) }}</textarea>

                </div>

            </div>

            <button
                type="submit"
                class="btn-primary"
            >

                Update Role

            </button>

        </form>

    </div>

</div>

@endsection