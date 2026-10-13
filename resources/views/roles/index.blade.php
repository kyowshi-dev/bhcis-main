@extends('layouts.app')

@section('title', 'Roles')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <x-flash />

    <x-page-header title="Roles"
                   :subtitle="__('Users inherit permissions from their role. Edit a role to adjust what its users can access.')" />

    <div class="overflow-hidden rounded-xl lg:rounded-2xl border bg-surface shadow-sm" style="border-color: var(--border);">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-teal-soft/60">
                    <tr>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted whitespace-nowrap">Role</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted whitespace-nowrap hidden sm:table-cell">Users</th>
                        <th class="px-3 lg:px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">Permissions</th>
                        <th class="px-3 lg:px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-ink-muted whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-surface divide-y divide-border">
                    @forelse ($roles as $role)
                        @php
                            $permissions = $role->permissions->values();
                            $visible = $permissions->take(6);
                            $overflow = max(0, $permissions->count() - 6);
                        @endphp
                        <tr class="hover:bg-black/[0.03] transition-colors">
                            <td class="px-3 lg:px-6 py-3 text-sm whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium" style="color: var(--ink);">{{ $role->role_name }}</span>
                                    @if ($role->role_name === 'Admin')
                                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                                              style="border-color: var(--border); background: var(--bg-surface-elevated); color: var(--ink-muted);">
                                            <i class="fa-solid fa-star text-[9px]" aria-hidden="true"></i> System
                                        </span>
                                    @endif
                                </div>
                                {{-- Users count duplicated on narrow viewports where the Users column is hidden --}}
                                <div class="text-xs mt-0.5 sm:hidden" style="color: var(--ink-muted);">
                                    {{ $role->users_count ?? 0 }} {{ \Illuminate\Support\Str::plural('user', (int) ($role->users_count ?? 0)) }}
                                </div>
                            </td>
                            <td class="px-3 lg:px-6 py-3 text-sm hidden sm:table-cell whitespace-nowrap" style="color: var(--ink-muted);">
                                {{ $role->users_count ?? 0 }} {{ \Illuminate\Support\Str::plural('user', (int) ($role->users_count ?? 0)) }}
                            </td>
                            <td class="px-3 lg:px-6 py-3 text-sm" style="color: var(--ink-muted);">
                                @if ($permissions->isEmpty())
                                    <span class="text-xs">No permissions assigned</span>
                                @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($visible as $permission)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border" style="border-color: var(--border); background: var(--bg-surface-elevated); color: var(--ink-muted);">
                                                {{ ucwords(str_replace('_', ' ', $permission->name)) }}
                                            </span>
                                        @endforeach
                                        @if ($overflow > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border" style="border-color: var(--border); background: var(--teal-soft); color: var(--primary);">
                                                +{{ $overflow }} more
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 lg:px-6 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('roles.edit', $role) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition hover:bg-primary/10"
                                   style="border-color: var(--primary); color: var(--primary);">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12">
                                <x-empty-state
                                    icon="fa-solid fa-user-shield"
                                    title="No roles found"
                                    description="Roles define what modules each user can access. They are part of the system role set."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
