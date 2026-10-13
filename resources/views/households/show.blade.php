@extends('layouts.app')

@section('title', 'Household - {{ $household->display_name }}')

@section('content')
<div class="space-y-5 lg:space-y-6" x-data="householdPage()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <x-page-header :title="$household->display_name" :subtitle="__('Household HH-:id · Zone :zone', ['id' => str_pad($household->id, 4, '0', STR_PAD_LEFT), 'zone' => $household->zone->zone_number ?? '&mdash;'])">
                <x-slot:actions>
                    <a href="{{ route('households.edit', $household->id) }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold transition"
                       style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                        Edit Household
                    </a>
                </x-slot:actions>
            </x-page-header>
        </div>

        <a href="{{ route('households.index') }}"
           class="inline-flex items-center justify-center px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl text-xs lg:text-sm font-semibold transition"
           style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
            Back to Households
        </a>
    </div>

    <x-flash />

    <!-- Household Details Card -->
    <div class="rounded-2xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="px-4 lg:px-5 py-3 border-b" style="border-color: var(--border); background: var(--bg-surface);">
            <h2 class="font-semibold" style="color: var(--ink);">Household Details</h2>
        </div>
        <div class="p-4 lg:p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Head of Household</p>
                <p class="text-lg font-medium" style="color: var(--ink);">{{ $household->display_name }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Zone</p>
                <p class="text-lg font-medium" style="color: var(--ink);">{{ $household->zone->zone_number ?? '&mdash;' }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Contact</p>
                <p class="text-lg font-medium" style="color: var(--ink-muted);">{{ $household->contact_number ?: '&mdash;' }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Residents</p>
                <p class="text-lg font-medium" style="color: var(--ink);">{{ $members->count() }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Linked Patients</p>
                <p class="text-lg font-medium" style="color: var(--primary);">{{ $patientCount }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Registered</p>
                <p class="text-lg font-medium" style="color: var(--ink-muted);">{{ $household->created_at ? \Illuminate\Support\Carbon::parse($household->created_at)->format('M d, Y') : '&mdash;' }}</p>
            </div>
        </div>
    </div>

    <!-- Resident Roster -->
    <div class="rounded-2xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="px-4 lg:px-5 py-3 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border); background: var(--bg-surface);">
            <h2 class="font-semibold" style="color: var(--ink);">Resident Roster</h2>
            <button type="button" @click="openAddMemberModal()"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition w-full sm:w-auto"
                    style="background: var(--primary);">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Resident
            </button>
        </div>

        @if ($members->isEmpty())
            <div class="p-8 text-center">
                <x-empty-state
                    icon="fa-solid fa-user-group"
                    title="No residents yet"
                    description="This household has no residents recorded. Add the first resident to start the census."
                >
                    <x-btn variant="primary" icon="fa-solid fa-plus" @click="openAddMemberModal()">Add Resident</x-btn>
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs lg:text-sm">
                    <thead>
                        <tr style="background: var(--teal-soft);">
                            <th class="px-3 py-2.5 lg:py-3 font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Name</th>
                            <th class="px-3 py-2.5 lg:py-3 font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Sex</th>
                            <th class="px-3 py-2.5 lg:py-3 font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Age</th>
                            <th class="px-3 py-2.5 lg:py-3 font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Relationship</th>
                            <th class="px-3 py-2.5 lg:py-3 font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Status</th>
                            <th class="px-3 py-2.5 lg:py-3 text-right font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr class="border-b last:border-b-0" style="border-color: var(--border);">
                                <td class="px-3 py-2.5 lg:py-3 font-medium" style="color: var(--ink);">
                                    {{ $member->full_name }}
                                    @if ($member->suffix)
                                        <span class="ml-1 text-xs" style="color: var(--ink-muted);">({{ $member->suffix }})</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 lg:py-3" style="color: var(--ink-muted);">
                                    {{ $member->sex }}
                                </td>
                                <td class="px-3 py-2.5 lg:py-3" style="color: var(--ink-muted);">
                                    {{ $member->age ?? '&mdash;' }}
                                    @if ($member->age !== null)
                                        <span class="text-xs ml-1" style="color: var(--ink-subtle);">y/o</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 lg:py-3" style="color: var(--ink);">
                                    {{ $member->relationship }}
                                </td>
                                <td class="px-3 py-2.5 lg:py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        @if ($member->patient_id)
                                            <x-badge variant="primary" icon="fa-solid fa-user-check">
                                                Patient
                                            </x-badge>
                                        @else
                                            <x-badge variant="outlined" icon="fa-solid fa-user">
                                                Resident
                                            </x-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 lg:py-3 text-right whitespace-nowrap space-x-1 flex justify-end items-center">
                                    <!-- Edit -->
                                    <button type="button"
                                            @click="openEditMemberModal({{ json_encode($member) }})"
                                            class="inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[11px] font-semibold transition"
                                            style="background: var(--primary-soft); color: var(--primary); border: 1px solid var(--border);"
                                            title="Edit resident">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                                    </button>

                                    <!-- Enroll as Patient (if not linked) -->
                                    @if (! $member->patient_id)
                                        <a href="{{ route('patients.create', ['household_id' => $household->id, 'member_id' => $member->id]) }}"
                                           class="inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[11px] font-semibold transition"
                                           style="background: var(--accent-blue-soft); color: var(--accent-blue); border: 1px solid var(--border);"
                                           title="Enroll as patient">
                                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Enroll
                                        </a>
                                    @endif

                                    <!-- Remove (if not linked to patient) -->
                                    @if (! $member->patient_id)
                                        <form method="POST" action="{{ route('households.members.destroy', [$household->id, $member->id]) }}" data-no-loading style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    @click="confirmDeleteMember($el.closest('form'), @js($member->full_name))"
                                                    class="inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[11px] font-semibold transition"
                                                    style="background: var(--danger-soft); color: var(--danger); border: 1px solid var(--border);"
                                                    title="Remove resident">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i> Remove
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Add Member Modal -->
<x-modal name="addMemberModal" title="Add Resident" maxWidth="max-w-md"
         x-on:open-add-member-modal.window="open = true"
         x-on:close.window="open = false">
    <form method="POST" action="{{ route('households.members.store', $household->id) }}" class="space-y-4">
        @csrf
        @include('households.partials.member-form', ['member' => null, 'isEdit' => false])

        <div class="flex gap-2 justify-end pt-4 border-t" style="border-color: var(--border);">
            <button type="button" @click="open = false"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold transition"
                    style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                Cancel
            </button>
            <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                    style="background: var(--primary);">
                Add Resident
            </button>
        </div>
    </form>
</x-modal>

<!-- Edit Member Modal -->
<x-modal name="editMemberModal" title="Edit Resident" maxWidth="max-w-md"
         x-on:open-edit-member-modal.window="open = true"
         x-on:close.window="open = false">
    <form method="POST" id="editMemberForm" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="member_id" id="edit_member_id">
        @include('households.partials.member-form', ['member' => null, 'isEdit' => true])

        <div class="flex gap-2 justify-end pt-4 border-t" style="border-color: var(--border);">
            <button type="button" @click="open = false"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold transition"
                    style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                Cancel
            </button>
            <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                    style="background: var(--primary);">
                Save Changes
            </button>
        </div>
    </form>
</x-modal>

<script>
function householdPage() {
    return {
        openAddMemberModal() {
            this.$dispatch('open-add-member-modal');
        },

        openEditMemberModal(member) {
            const form = document.getElementById('editMemberForm');
            if (!form) return;

            document.getElementById('edit_member_id').value = member.id;
            document.getElementById('edit_first_name').value = member.first_name;
            document.getElementById('edit_middle_name').value = member.middle_name ?? '';
            document.getElementById('edit_last_name').value = member.last_name;
            document.getElementById('edit_suffix').value = member.suffix ?? '';
            document.getElementById('edit_sex').value = member.sex;
            document.getElementById('edit_date_of_birth').value = member.date_of_birth ?? '';
            document.getElementById('edit_age').value = member.age ?? '';
            document.getElementById('edit_relationship').value = member.relationship;

            form.action = "{{ route('households.members.update', [$household->id, ':memberId']) }}".replace(':memberId', member.id);

            this.$dispatch('open-edit-member-modal');
        },

        confirmDeleteMember(form, name) {
            Swal.fire({
                title: 'Remove Resident?',
                text: 'This will permanently remove "' + name + '" from this household. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--danger)',
                cancelButtonColor: 'var(--ink-muted)',
                confirmButtonText: 'Yes, remove'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    };
}
</script>
@endsection