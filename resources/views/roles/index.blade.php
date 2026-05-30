@extends('layouts.app')

@section('content')

<style>

.roles-page{
    padding:18px;
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:18px;
    gap:14px;
    flex-wrap:wrap;
}

.page-title{
    font-size:32px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:4px;
    line-height:1.05;
}

.page-subtitle{
    color:#64748b;
    font-size:15px;
}

.add-role-btn{
    background:#2563eb;
    color:white;
    padding:10px 16px;
    border-radius:10px;
    text-decoration:none;
    font-weight:700;
    font-size:14px;
    transition:0.2s ease;
}

.add-role-btn:hover{
    background:#1d4ed8;
}

.roles-grid{
    display:grid;
    grid-template-columns:
        repeat(auto-fill, minmax(220px, 1fr));
    align-items:start;
    gap:14px;
}

.role-card{
    background:white;
    border-radius:14px;
    padding:14px;
    box-shadow:0 2px 8px rgba(15,23,42,0.06);
    border:1px solid #e5e7eb;
    transition:0.2s ease;
}

.role-card:hover{
    transform:translateY(-2px);
    border-color:#c7d2fe;
    box-shadow:0 8px 20px rgba(15,23,42,0.08);
}

.role-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:10px;
    margin-bottom:12px;
}

.role-code{
    display:inline-block;
    background:#dbeafe;
    color:#2563eb;
    font-size:10px;
    font-weight:700;
    padding:5px 9px;
    border-radius:999px;
    margin-bottom:10px;
    max-width:150px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.role-id{
    font-size:13px;
    font-weight:700;
    color:#64748b;
    white-space:nowrap;
}

.role-name{
    font-size:17px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:0;
    line-height:1.2;

    word-break:break-word;
    overflow-wrap:anywhere;
}

.role-description{
    color:#475569;
    line-height:1.45;
    margin-bottom:14px;
    min-height:36px;
    font-size:12px;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
}

.role-footer{
    margin-bottom:14px;
}

.role-badge{
    display:inline-block;
    padding:7px 11px;
    border-radius:999px;
    font-size:11px;
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
    gap:8px;
    flex-wrap:wrap;
}

.btn-edit{
    background:#2563eb;
    color:white;
    padding:9px 12px;
    border-radius:9px;
    text-decoration:none;
    font-weight:700;
    font-size:12px;
    transition:0.2s ease;
}

.btn-edit:hover{
    background:#1d4ed8;
}

.btn-permissions{
    background:#16a34a;
    color:white;
    padding:9px 12px;
    border-radius:9px;
    text-decoration:none;
    font-weight:700;
    font-size:12px;
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

@media (max-width: 768px){
    .roles-page{
        padding:10px 0 16px;
    }

    .page-title{
        font-size:26px;
    }

    .page-subtitle{
        font-size:13px;
    }

    .roles-grid{
        grid-template-columns:repeat(auto-fill, minmax(150px, 1fr));
        gap:10px;
    }

    .role-card{
        border-radius:12px;
        padding:11px;
    }

    .role-code{
        max-width:104px;
        font-size:9px;
        padding:4px 7px;
    }

    .role-name{
        font-size:14px;
    }

    .role-description{
        min-height:32px;
        font-size:11px;
    }

    .role-badge{
        font-size:10px;
        padding:6px 9px;
    }

    .btn-edit,
    .btn-permissions{
        flex:1;
        min-width:0;
        padding:8px 9px;
        text-align:center;
        font-size:11px;
    }
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
