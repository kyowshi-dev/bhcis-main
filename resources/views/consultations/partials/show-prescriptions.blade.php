<div class="space-y-3 pt-2 border-t" style="border-color: var(--border);">
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <h3 class="font-medium text-sm" style="color: var(--ink);">Prescription (Rx)</h3>
            @if($prescriptions->count() > 0)
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-[var(--primary)] text-white">{{ $prescriptions->count() }}</span>
            @endif
        </div>
        @if ($clinicalReviewOpen && $canAddPrescription)
            <button type="button" @click="$dispatch('open-prescription-modal')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-medium transition flex items-center gap-1" style="background: var(--teal-soft); color: var(--primary);" title="P - Add prescription">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add
            </button>
        @endif
    </div>

    @if($prescriptions->count() > 0)
        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
            @foreach($prescriptions as $rx)
                <div class="rounded-lg border p-3 text-sm" style="border-color: var(--border); background: var(--bg);">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium truncate" style="color: var(--ink);">{{ $rx->medicine_name }}</span>
                                @if ($rx->route)
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase" style="background: var(--teal-soft); color: var(--primary);">{{ $rx->route }}</span>
                                @endif
                                @if ($rx->is_custom)
                                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide" style="background: var(--border); color: var(--ink-muted);">Custom</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs" style="color: var(--ink-muted);">
                                @php
                                    $sigParts = array_filter([
                                        $rx->dosage,
                                        $rx->frequency,
                                        $rx->duration ? 'x ' . $rx->duration : null,
                                        $rx->quantity ? 'Qty ' . $rx->quantity : null,
                                    ]);
                                @endphp
                                {{ implode(' · ', $sigParts) ?: '—' }}
                            </p>
                            @if ($rx->instructions)
                                <p class="mt-0.5 text-xs italic" style="color: var(--ink-muted);">{{ $rx->instructions }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-0.5 shrink-0">
                            <button type="button" @click="$dispatch('edit-prescription', { id: {{ $rx->id }}, name: {{ \Illuminate\Support\Js::from($rx->medicine_name) }}, dosage: {{ \Illuminate\Support\Js::from($rx->dosage) }}, route: {{ \Illuminate\Support\Js::from($rx->route) }}, frequency: {{ \Illuminate\Support\Js::from($rx->frequency) }}, duration: {{ \Illuminate\Support\Js::from($rx->duration) }}, quantity: {{ \Illuminate\Support\Js::from($rx->quantity) }}, instructions: {{ \Illuminate\Support\Js::from($rx->instructions) }}, medicineId: {{ \Illuminate\Support\Js::from($rx->medicine_id ?? '') }} })"
                                    class="rounded p-1.5 text-ink-subtle transition hover:text-[var(--primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-blue"
                                    title="Edit prescription" aria-label="Edit prescription {{ $rx->medicine_name }}">
                                <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                            </button>
                            <button type="button" @click="$dispatch('delete-prescription', { id: {{ $rx->id }}})"
                                    class="rounded p-1.5 text-ink-subtle transition hover:text-[var(--danger)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-blue"
                                    title="Delete prescription" aria-label="Delete prescription {{ $rx->medicine_name }}">
                                <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-6 text-center rounded-lg border-2 border-dashed" style="border-color: var(--border);">
            <i class="fa-solid fa-prescription-bottle-alt text-3xl mb-2" style="color: var(--ink-subtle);"></i>
            <p class="text-sm" style="color: var(--ink-muted);">No prescriptions recorded</p>
            @if ($clinicalReviewOpen && $canAddPrescription)
                <button type="button" @click="$dispatch('open-prescription-modal')" class="mt-2 px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1 mx-auto" style="background: var(--teal-soft); color: var(--primary);">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add first prescription
                </button>
            @elseif ($clinicalReviewOpen && !$canAddPrescription)
                <p class="mt-2 text-xs" style="color: var(--ink-muted);">Only doctors and admins can add prescriptions.</p>
            @else
                <p class="mt-2 text-xs" style="color: var(--ink-muted);">Opens after nurse validation.</p>
            @endif
        </div>
    @endif
</div>
