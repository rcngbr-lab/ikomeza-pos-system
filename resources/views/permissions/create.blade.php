@extends('layouts.app')

@section('content')

<style>

.page-container{
    max-width:700px;
    margin:auto;
    padding:20px;
}

.card{
    background:white;
    border-radius:12px;
    padding:30px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.page-title{
    font-size:30px;
    font-weight:700;
    margin-bottom:25px;
}

.form-group{
    margin-bottom:20px;
}

.form-label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:#374151;
}

.form-input{
    width:100%;
    padding:14px;
    border:1px solid #d1d5db;
    border-radius:8px;
    font-size:15px;
}

.form-input:focus{
    outline:none;
    border-color:#2563eb;
}

.submit-btn{
    background:#2563eb;
    color:white;
    border:none;
    padding:14px 24px;
    border-radius:8px;
    font-weight:700;
    cursor:pointer;
}

.error-box{
    background:#fee2e2;
    color:#991b1b;
    padding:14px;
    border-radius:8px;
    margin-bottom:20px;
}

</style>

<div class="page-container">

    <div class="card">

        <h1 class="page-title">
            Create Permission
        </h1>

        @if($errors->any())

            <div class="error-box">

                <ul style="margin:0; padding-left:20px;">

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

        <form
            action="{{ route('permissions.store') }}"
            method="POST"
        >

            @csrf

            <div class="form-group">

                <label class="form-label">
                    Permission Code
                </label>

                <input
                    type="text"
                    name="code"
                    class="form-input"
                    placeholder="manage_products"
                    value="{{ old('code') }}"
                    required
                >

            </div>

            <div class="form-group">

                <label class="form-label">
                    Permission Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-input"
                    placeholder="Manage Products"
                    value="{{ old('name') }}"
                    required
                >

            </div>

            <div class="form-group">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    class="form-input"
                    rows="4"
                    placeholder="Permission description"
                >{{ old('description') }}</textarea>

            </div>

            <button
                type="submit"
                class="submit-btn"
            >
                Save Permission
            </button>

        </form>

    </div>

</div>

@endsection