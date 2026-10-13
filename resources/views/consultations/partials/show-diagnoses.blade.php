<div class="space-y-3">
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <h3 class="font-medium text-sm" style="color: var(--ink);">Medical Diagnosis</h3>
            @if($diagnoses->count() > 0)
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-[var(--primary)] text-white">{{ $diagnoses->count() }}</span>
            @endif
        </div>
        @if ($clinicalReviewOpen && $canAddDiagnosis)
            <button type="button" @click="$dispatch('open-diagnosis-modal')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-medium transition flex items-center gap-1" style="background: var(--teal-soft); color: var(--primary);" title="D - Add diagnosis">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add
            </button>
        @endif
    </div>

    @if($diagnoses->count() > 0)
        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
            @foreach($diagnoses as $d)
                <div class="rounded-lg border p-3 text-sm flex items-start justify-between gap-2 group" style="border-color: var(--border); background: var(--bg);">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if ($d->diagnosis_code)
                                <span class="font-semibold text-[var(--primary)] whitespace-nowrap">{{ $d->diagnosis_code }}</span>
                                <span style="color: var(--ink);">{{ $d->diagnosis_name }}</span>
                            @else
                                <span class="font-semibold" style="color: var(--ink);">{{ $d->diagnosis_name }}</span>
                            @endif
                            @if ($d->is_custom)
                                <span class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide" style="background: var(--border); color: var(--ink-muted);">Custom</span>
                            @endif
                        </div>
                        @if ($d->remarks)
                            <p class="text-xs mt-1 truncate" style="color: var(--ink-muted);">{{ $d->remarks }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-0.5 shrink-0">
                        <button type="button" @click="$dispatch('edit-diagnosis', { id: {{ $d->id }}, name: {{ \Illuminate\Support\Js::from($d->diagnosis_name) }}, remarks: {{ \Illuminate\Support\Js::from($d->remarks ?? '') }}, lookupId: {{ \Illuminate\Support\Js::from($d->diagnosis_id ?? '') }} })" class="rounded p-1.5 text-ink-subtle transition hover:text-[var(--primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-blue" title="Edit diagnosis" aria-label="Edit diagnosis {{ $d->diagnosis_name }}">
                            <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                        </button>
                        <button type="button" @click="$dispatch('delete-diagnosis', { id: {{ $d->id }}})" class="rounded p-1.5 text-ink-subtle transition hover:text-[var(--danger)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-blue" title="Delete diagnosis" aria-label="Delete diagnosis {{ $d->diagnosis_name }}">
                            <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-6 text-center rounded-lg border-2 border-dashed" style="border-color: var(--border);">
            <i class="fa-solid fa-stethoscope text-3xl mb-2" style="color: var(--ink-subtle);"></i>
            <p class="text-sm" style="color: var(--ink-muted);">No diagnoses recorded</p>
            @if ($clinicalReviewOpen && $canAddDiagnosis)
                <button type="button" @click="$dispatch('open-diagnosis-modal')" class="mt-2 px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1 mx-auto" style="background: var(--teal-soft); color: var(--primary);">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add first diagnosis
                </button>
            @elseif ($clinicalReviewOpen && !$canAddDiagnosis)
                <p class="mt-2 text-xs" style="color: var(--ink-muted);">Only doctors can add diagnoses.</p>
            @else
                <p class="mt-2 text-xs" style="color: var(--ink-muted);">Opens after nurse validation.</p>
            @endif
        </div>
    @endif
</div>