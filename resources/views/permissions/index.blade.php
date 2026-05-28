@extends('layouts.app')

@section('content')

<style>

.page-container{
    max-width:1100px;
    margin:auto;
    padding:20px;
}

.card{
    background:white;
    border-radius:12px;
    padding:25px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.page-title{
    font-size:32px;
    font-weight:700;
}

.btn{
    padding:10px 18px;
    border:none;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
    cursor:pointer;
    display:inline-block;
}

.btn-blue{
    background:#2563eb;
    color:white;
}

.btn-red{
    background:#dc2626;
    color:white;
}

.btn-green{
    background:#16a34a;
    color:white;
}

.permissions-table{
    width:100%;
    border-collapse:collapse;
}

.permissions-table th{
    background:#f3f4f6;
    padding:14px;
    text-align:left;
    font-size:15px;
}

.permissions-table td{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
    vertical-align:middle;
}

.action-buttons{
    display:flex;
    gap:10px;
}

.delete-form{
    margin:0;
}

.permission-code{
    font-family:monospace;
    color:#374151;
}

</style>

<div class="page-container">

    <div class="card">

        <div class="page-header">

            <h1 class="page-title">
                Permissions
            </h1>

            <a
                href="{{ route('permissions.create') }}"
                class="btn btn-blue"
            >
                Create Permission
            </a>

        </div>

        @if(session('success'))

            <div
                style="
                    background:#dcfce7;
                    color:#166534;
                    padding:15px;
                    border-radius:8px;
                    margin-bottom:20px;
                    font-weight:600;
                "
            >
                {{ session('success') }}
            </div>

        @endif

        <table class="permissions-table">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th width="180">Actions</th>
                </tr>

            </thead>

            <tbody>

                @forelse($permissions as $permission)

                    <tr>

                        <td>
                            {{ $permission->id }}
                        </td>

                        <td class="permission-code">
                            {{ $permission->code }}
                        </td>

                        <td>
                            {{ $permission->name }}
                        </td>

                        <td>

                            <div class="action-buttons">

                                <a
                                    href="{{ route('permissions.edit', $permission->id) }}"
                                    class="btn btn-blue"
                                >
                                    Edit
                                </a>

                                <form
                                    action="{{ route('permissions.destroy', $permission->id) }}"
                                    method="POST"
                                    class="delete-form"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-red"
                                        onclick="return confirm('Delete this permission?')"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4">

                            No permissions found.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection