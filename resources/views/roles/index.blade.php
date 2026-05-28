@extends('layouts.app')

@section('content')

<style>

.roles-page{
    padding:30px;
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    gap:20px;
    flex-wrap:wrap;
}

.page-title{
    font-size:38px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:6px;
}

.page-subtitle{
    color:#64748b;
    font-size:15px;
}

.add-role-btn{
    background:#2563eb;
    color:white;
    padding:14px 22px;
    border-radius:12px;
    text-decoration:none;
    font-weight:700;
    transition:0.2s ease;
}

.add-role-btn:hover{
    background:#1d4ed8;
}

.roles-grid{
    display:grid;
    grid-template-columns:
        repeat(auto-fit, minmax(340px, 1fr));
    gap:25px;
}

.role-card{
    background:white;
    border-radius:18px;
    padding:22px;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
    border:1px solid #e5e7eb;
    transition:0.2s ease;
}

.role-card:hover{
    transform:translateY(-3px);
}

.role-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:20px;
}

.role-code{
    display:inline-block;
    background:#dbeafe;
    color:#2563eb;
    font-size:13px;
    font-weight:700;
    padding:8px 14px;
    border-radius:999px;
    margin-bottom:16px;
}

.role-id{
    font-size:18px;
    font-weight:700;
    color:#64748b;
}

.role-name{
    font-size:22px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:14px;
    line-height:1.3;

    word-break:break-word;
    overflow-wrap:anywhere;
}

.role-description{
    color:#475569;
    line-height:1.6;
    margin-bottom:20px;
    min-height:70px;
    font-size:14px;
    white-space:pre-line;
}

.role-footer{
    margin-bottom:25px;
}

.role-badge{
    display:inline-block;
    padding:10px 18px;
    border-radius:999px;
    font-size:14px;
    font-weight:700;
}

.admin-badge{
    background:#dcfce7;
    color:#166534;
}

.cashier-badge{
    background:#dbeafe;
    color:#1d4ed8;
}

.default-badge{
    background:#f1f5f9;
    color:#334155;
}

.role-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.btn-edit{
    background:#2563eb;
    color:white;
    padding:12px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:700;
    transition:0.2s ease;
}

.btn-edit:hover{
    background:#1d4ed8;
}

.btn-permissions{
    background:#16a34a;
    color:white;
    padding:12px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:700;
    transition:0.2s ease;
}

.btn-permissions:hover{
    background:#15803d;
}

.empty-state{
    background:white;
    padding:50px;
    border-radius:16px;
    text-align:center;
    border:1px dashed #cbd5e1;
}

.empty-state h3{
    font-size:26px;
    margin-bottom:10px;
    color:#0f172a;
}

.empty-state p{
    color:#64748b;
}

</style>

<div class="roles-page">

    {{-- PAGE HEADER --}}
    <div class="page-header">

        <div>

            <h1 class="page-title">
                Roles Management
            </h1>

            <p class="page-subtitle">
                Manage permissions and system access levels
            </p>

        </div>

        <a
            href="/roles/create"
            class="add-role-btn"
        >
            + Add Role
        </a>

    </div>

    {{-- ROLE CARDS --}}
    <div class="roles-grid">

        @forelse($roles as $role)

            <div class="role-card">

                {{-- TOP --}}
                <div class="role-top">

                    <div>

                        <div class="role-code">
                            {{ $role->code }}
                        </div>

                        <h2 class="role-name">
                            {{ $role->name }}
                        </h2>

                    </div>

                    <div class="role-id">
                        #{{ $role->id }}
                    </div>

                </div>

                {{-- DESCRIPTION --}}
                <div class="role-description">

                    {{ $role->description ?? 'No description available' }}

                </div>

                {{-- ACCESS BADGE --}}
                <div class="role-footer">

                    @if($role->code === 'ADMIN')

                        <span class="role-badge admin-badge">
                            Full Access
                        </span>

                    @elseif($role->code === 'CASHIER')

                        <span class="role-badge cashier-badge">
                            POS Operations
                        </span>

                    @else

                        <span class="role-badge default-badge">
                            Standard Access
                        </span>

                    @endif

                </div>

                {{-- ACTIONS --}}
                <div class="role-actions">

                    <a
                        href="{{ route('roles.edit', $role->id) }}"
                        class="btn-edit"
                    >
                        Edit
                    </a>

                    <a
                        href="{{ route('roles.permissions', $role->id) }}"
                        class="btn-permissions"
                    >
                        Permissions
                    </a>

                </div>

            </div>

        @empty

            <div class="empty-state">

                <h3>
                    No Roles Found
                </h3>

                <p>
                    Create your first role to continue.
                </p>

            </div>

        @endforelse

    </div>

</div>

@endsection