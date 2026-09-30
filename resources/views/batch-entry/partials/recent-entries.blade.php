{{-- Recent Entries - Shows last 10 patients registered via batch entry --}}
<div class="bg-surface rounded-xl lg:rounded-lg shadow-sm border border-border">

    <div class="px-4 lg:px-6 py-3 border-b border-border" style="background: var(--teal-soft);">
        <h2 class="text-sm lg:text-base font-extrabold flex items-center" style="color: var(--ink);">
            <span class="mr-2"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);" aria-hidden="true"></i></span>
            Recent Patient Entries
        </h2>
        <p class="text-xs" style="color: var(--ink-muted);">Last 10 patients registered. Click to view profile or edit.</p>
    </div>

    @if ($recentPatients->isEmpty())
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background: var(--teal-soft);">
                <i class="fa-solid fa-user-plus text-lg" style="color: var(--primary);" aria-hidden="true"></i>
            </div>
            <p class="text-sm font-medium" style="color: var(--ink);">No patients registered yet</p>
            <p class="text-xs mt-1" style="color: var(--ink-muted);">Use the Patient Registration tab above to start entering data from paper forms.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border" style="background: var(--teal-soft);">
                        <th class="px-4 py-2.5 text-left text-xs font-semibold" style="color: var(--ink-muted);">Patient</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold hidden sm:table-cell" style="color: var(--ink-muted);">Code</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold hidden md:table-cell" style="color: var(--ink-muted);">Sex</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold hidden md:table-cell" style="color: var(--ink-muted);">Birthdate</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold hidden lg:table-cell" style="color: var(--ink-muted);">Household</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold" style="color: var(--ink-muted);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentPatients as $patient)
                        <tr class="border-b border-border last:border-0 hover:bg-black/[0.03] transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0" style="background: var(--primary);">
                                        {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium truncate" style="color: var(--ink);">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name) }}</p>
                                        <p class="text-xs" style="color: var(--ink-muted);">{{ $patient->civil_status ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="text-xs font-mono" style="color: var(--ink-muted);">{{ $patient->patient_code }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs" style="color: var(--ink);">{{ $patient->sex }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs" style="color: var(--ink);">{{ $patient->date_of_birth->format('M d, Y') }}</span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span class="text-xs" style="color: var(--ink-muted);">{{ $patient->household?->family_name_head ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('patients.show', $patient->id) }}"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition-colors hover:bg-black/5"
                                       style="color: var(--primary);"
                                       title="View Profile">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('batch-entry.patient.edit', $patient->id) }}"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition-colors hover:bg-black/5"
                                       style="color: var(--ink-muted);"
                                       title="Edit">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
