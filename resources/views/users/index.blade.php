@extends('layouts.app')

@section('content')
<div class="dense-page">
    <div class="dense-header">
        <div>
            <p class="dense-eyebrow">Security</p>
            <h1 class="dense-title">Users</h1>
            <p class="dense-subtitle">Staff accounts, username login, branch, department, role, and status.</p>
        </div>

        <a href="{{ route('users.create') }}" class="dense-btn-primary">+ Create User</a>
    </div>

    <section class="dense-card">
        <div class="dense-card-header">
            <div>
                <h2 class="text-sm font-black text-slate-950">Staff Register</h2>
                <p class="text-xs text-slate-500">Permissions are assigned from the selected role during create/edit.</p>
            </div>
            <p class="text-xs font-bold text-slate-500">{{ $users->total() }} users</p>
        </div>

        <div class="dense-table-wrap">
            <table class="dense-table min-w-[900px]">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <p class="font-black text-slate-950">{{ $user->name }}</p>
                                <p class="text-[11px] text-slate-500">
                                    {{ !str_ends_with(strtolower((string) $user->email), '@frontier.local') ? $user->email : 'No contact email' }}
                                </p>
                            </td>
                            <td><span class="dense-badge bg-indigo-100 text-indigo-700">{{ $user->username ?? '-' }}</span></td>
                            <td><span class="dense-badge bg-blue-100 text-blue-700">{{ $user->roles->first()?->name ?? $user->roleLabel() }}</span></td>
                            <td>{{ $user->department->name ?? 'All' }}</td>
                            <td>{{ $user->branch->name ?? 'N/A' }}</td>
                            <td>
                                @if($user->status === 'ACTIVE')
                                    <span class="dense-badge bg-emerald-100 text-emerald-700">ACTIVE</span>
                                @elseif($user->status === 'INACTIVE')
                                    <span class="dense-badge bg-amber-100 text-amber-700">INACTIVE</span>
                                @else
                                    <span class="dense-badge bg-rose-100 text-rose-700">SUSPENDED</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('users.edit', $user) }}" class="dense-btn-dark">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="dense-empty">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-3 py-2">
            {{ $users->onEachSide(1)->links() }}
        </div>
    </section>
</div>
@endsection
