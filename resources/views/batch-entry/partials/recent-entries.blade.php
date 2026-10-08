{{-- Recent Entries - Shows last 10 patients registered via batch entry --}}
<div class="bg-surface rounded-xl lg:rounded-lg shadow-sm border border-border" x-data="recentEntries()" x-init="init({{ json_encode($recentPatients) }})">

    <div class="px-4 lg:px-6 py-3 border-b border-border" style="background: var(--teal-soft);">
        <h2 class="text-sm lg:text-base font-extrabold flex items-center justify-between" style="color: var(--ink);">
            <span class="mr-2"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);" aria-hidden="true"></i></span>
            Recent Patient Entries
            <span class="ml-2 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold text-white" style="background: var(--primary);" x-text="entries.length"></span>
        </h2>
        <p class="text-xs" style="color: var(--ink-muted);">Last 10 patients registered. Click to view profile or edit.</p>
    </div>

    <template x-if="entries.length === 0">
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background: var(--teal-soft);">
                <i class="fa-solid fa-user-plus text-lg" style="color: var(--primary);" aria-hidden="true"></i>
            </div>
            <p class="text-sm font-medium" style="color: var(--ink);">No patients registered yet</p>
            <p class="text-xs mt-1" style="color: var(--ink-muted);">Use the Patient Registration tab above to start entering data from paper forms.</p>
        </div>
    </template>

    <template x-if="entries.length > 0">
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
                    <template x-for="patient in entries" :key="patient.id">
                        <tr class="border-b border-border last:border-0 hover:bg-black/[0.03] transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0" style="background: var(--primary);" x-text="patient.initials"></div>
                                    <div class="min-w-0">
                                        <p class="font-medium truncate" style="color: var(--ink);" x-text="patient.name"></p>
                                        <p class="text-xs" style="color: var(--ink-muted);" x-text="patient.civil_status"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="text-xs font-mono" style="color: var(--ink-muted);" x-text="patient.patient_code"></span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs" style="color: var(--ink);" x-text="patient.sex"></span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs" style="color: var(--ink);" x-text="patient.birthdate_formatted"></span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span class="text-xs" style="color: var(--ink-muted);" x-text="patient.household"></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a :href="'/patients/' + patient.id"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition-colors hover:bg-black/5"
                                       style="color: var(--primary);"
                                       title="View Profile">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>
                                    <a :href="'/batch-entry/patient/' + patient.id + '/edit'"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium transition-colors hover:bg-black/5"
                                       style="color: var(--ink-muted);"
                                       title="Edit">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

</div>

<script>
function recentEntries() {
    return {
        entries: [],

        init(serverEntries) {
            this.entries = serverEntries.map(p => this.transformPatient(p));
            // Listen for new patients added via AJAX from the registration form
            window.addEventListener('patient-added', (e) => {
                const newPatient = e.detail;
                if (newPatient) {
                    this.entries.unshift(this.transformPatient(newPatient));
                    // Keep only last 10
                    if (this.entries.length > 10) {
                        this.entries = this.entries.slice(0, 10);
                    }
                    // Notify parent of count change
                    this.$dispatch('recent-entries-updated', { count: this.entries.length });
                }
            });
        },

        transformPatient(p) {
            return {
                id: p.id,
                initials: (p.first_name?.charAt(0) || '') + (p.last_name?.charAt(0) || '').toUpperCase(),
                name: [p.last_name, p.first_name, p.middle_name].filter(Boolean).join(' '),
                patient_code: p.patient_code,
                sex: p.sex,
                birthdate_formatted: p.date_of_birth ? new Date(p.date_of_birth).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '',
                household: p.household?.family_name_head || 'N/A',
                civil_status: p.civil_status || '',
            };
        },
    };
}
</script>