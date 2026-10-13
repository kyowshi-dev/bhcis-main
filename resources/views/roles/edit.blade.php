@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <x-flash />

    <x-page-header title="Edit Role"
                   :subtitle="'Update permissions for the ' . $role->role_name . ' role.'">
        <x-slot:actions>
            <x-btn href="{{ route('roles.index') }}" variant="outlined" icon="fa-solid fa-arrow-left">
                Back to Roles
            </x-btn>
        </x-slot:actions>
    </x-page-header>

    <form action="{{ route('roles.update', $role) }}" method="POST" class="space-y-4 lg:space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl lg:rounded-2xl border bg-surface shadow-sm p-4 lg:p-6" style="border-color: var(--border);">
            <x-field name="role_name" label="Role Name" id="role_name" required help="Displayed in the sidebar and on user records.">
                <x-slot:control>
                    <input
                        type="text"
                        id="role_name"
                        name="role_name"
                        value="{{ old('role_name', $role->role_name) }}"
                        class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                        required
                    >
                </x-slot:control>
            </x-field>
        </div>

        <div class="rounded-xl lg:rounded-2xl border bg-surface shadow-sm" style="border-color: var(--border);">
            <div class="p-4 lg:p-6 pb-0 lg:pb-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base lg:text-lg font-semibold" style="color: var(--ink);">Module Permissions</h2>
                        <p class="text-sm mt-1" style="color: var(--ink-muted);">
                            Users assigned to this role inherit these permissions.
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span id="permissionCount" class="text-xs font-medium" style="color: var(--ink-muted);" aria-live="polite"></span>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="selectAllPermissions" class="h-4 w-4 rounded border-border focus:ring-accent-blue">
                            <span class="text-sm font-medium" style="color: var(--ink);">Select All</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-4 lg:p-6 space-y-6">
                @php
                    $selectedPermissionIds = array_map('intval', old('permissions', $role->permissions->pluck('id')->all()));

                    $permissionGroups = [
                        [
                            'label' => 'Patient Care & Census',
                            'icon' => 'fa-solid fa-stethoscope',
                            'names' => ['household', 'patients', 'patients_destroy', 'consultations', 'referrals', 'immunizations', 'maternal'],
                        ],
                        [
                            'label' => 'Inventory & Reporting',
                            'icon' => 'fa-solid fa-file-medical',
                            'names' => ['medicines', 'reports', 'print_handouts', 'dashboard_handouts'],
                        ],
                        [
                            'label' => 'Administration & Data Entry',
                            'icon' => 'fa-solid fa-user-shield',
                            'names' => ['users', 'zones', 'nurse_batch_entry'],
                        ],
                    ];

                    $groupedPermissions = [];
                    $otherPermissions = [];
                    $knownGroupNames = array_merge(...array_map(fn (array $group): array => $group['names'], $permissionGroups));

                    foreach ($permissions as $permission) {
                        if (! in_array($permission->name, $knownGroupNames, true)) {
                            $otherPermissions[] = $permission;
                            continue;
                        }

                        foreach ($permissionGroups as $group) {
                            if (in_array($permission->name, $group['names'], true)) {
                                $groupedPermissions[$group['label']][] = $permission;
                                break;
                            }
                        }
                    }

                    if ($otherPermissions !== []) {
                        $permissionGroups[] = [
                            'label' => 'Other',
                            'icon' => 'fa-solid fa-puzzle-piece',
                            'names' => array_map(fn ($permission): string => $permission->name, $otherPermissions),
                        ];
                    }
                @endphp

                @foreach ($permissionGroups as $group)
                    @php
                        $groupPermissions = $groupedPermissions[$group['label']] ?? [];
                    @endphp
                    @if ($groupPermissions === [])
                        @continue
                    @endif

                    <section aria-label="{{ $group['label'] }} permissions" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">
                                <i class="{{ $group['icon'] }}" aria-hidden="true"></i>
                                {{ $group['label'] }}
                            </h3>
                            <span class="text-xs" style="color: var(--ink-muted);" data-group-count="{{ $group['label'] }}"></span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($groupPermissions as $permission)
                                @php
                                    $isLocked = $role->role_name === 'Admin' && $permission->name === 'users';
                                    $permissionLabel = ucwords(str_replace('_', ' ', $permission->name));
                                @endphp
                                <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors hover:bg-black/[0.03]"
                                       style="border-color: var(--border); background: var(--bg-surface-elevated);">
                                    @if ($isLocked)
                                        <input type="hidden" name="permissions[]" value="{{ $permission->id }}">
                                    @endif
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission->id }}"
                                        class="permission-checkbox h-4 w-4 mt-0.5 rounded border-border focus:ring-accent-blue"
                                        @checked($isLocked || in_array($permission->id, $selectedPermissionIds, true))
                                        @disabled($isLocked)
                                        data-name="{{ $permission->name }}"
                                        data-group="{{ $group['label'] }}"
                                    >
                                    <div class="min-w-0">
                                        <div class="font-medium text-sm flex items-center gap-1.5 flex-wrap" style="color: var(--ink);">
                                            {{ $permissionLabel }}
                                            @if ($isLocked)
                                                <i class="fa-solid fa-lock text-[10px]" aria-hidden="true" title="Required for the Admin role"></i>
                                                <span class="text-xs font-normal" style="color: var(--ink-muted);">(required)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ $permission->description }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            @error('permissions')
                <p class="px-4 lg:px-6 pb-4 lg:pb-6 text-xs text-danger" data-auto-dismiss>{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 lg:gap-3">
            <x-btn href="{{ route('roles.index') }}" variant="ghost">Cancel</x-btn>
            <x-btn type="submit" variant="primary" icon="fa-solid fa-check">Update Role</x-btn>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAllPermissions');
        const counter = document.getElementById('permissionCount');

        function enabledCheckboxes() {
            return Array.from(document.querySelectorAll('.permission-checkbox')).filter(function (c) {
                return !c.disabled;
            });
        }

        function updateSummary() {
            const enabled = enabledCheckboxes();
            const checked = enabled.filter(function (c) { return c.checked; });

            if (counter) {
                counter.textContent = checked.length + ' of ' + enabled.length + ' selected';
            }

            if (selectAll) {
                selectAll.checked = enabled.length > 0 && enabled.length === checked.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < enabled.length;
            }

            document.querySelectorAll('[data-group-count]').forEach(function (el) {
                const group = el.getAttribute('data-group-count');
                const boxes = enabled.filter(function (c) { return c.getAttribute('data-group') === group; });
                const groupChecked = boxes.filter(function (c) { return c.checked; }).length;
                el.textContent = groupChecked + ' of ' + boxes.length;
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                enabledCheckboxes().forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                updateSummary();
            });
        }

        document.addEventListener('change', function (e) {
            if (e.target.classList && e.target.classList.contains('permission-checkbox')) {
                updateSummary();
            }
        });

        updateSummary();
    });
</script>
@endsection
