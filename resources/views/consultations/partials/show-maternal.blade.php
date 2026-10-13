<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('maternal.prenatal.patient', $patient->id) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[11px] font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--primary);">
            <i class="fa-solid fa-baby-carriage" aria-hidden="true"></i> Prenatal
        </a>
        <a href="{{ route('maternal.postnatal.patient', $patient->id) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[11px] font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--primary);">
            <i class="fa-solid fa-child-reaching" aria-hidden="true"></i> Postpartum
        </a>
        <a href="{{ route('maternal.family-planning.patient', $patient->id) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[11px] font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--primary);">
            <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i> Family Planning
        </a>
    </div>

    @if ($linkedPrenatalVisits->isEmpty() && $linkedPostnatal === null && $linkedFpVisits->isEmpty())
        <p class="text-sm text-center py-4" style="color: var(--ink-muted);">No maternal records linked to this consultation.</p>
    @else
        @if ($linkedPrenatalVisits->isNotEmpty())
            <div class="space-y-2">
                <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Prenatal visits</p>
                <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                    @foreach ($linkedPrenatalVisits as $pv)
                        <div class="rounded-lg border px-3 py-2 text-sm flex items-center justify-between" style="border-color: var(--border); background: var(--bg);">
                            <span class="font-medium" style="color: var(--ink);">{{ \Carbon\Carbon::parse($pv->visit_date)->format('M d, Y') }}</span>
                            <span class="text-[11px]" style="color: var(--ink-muted);">FH {{ $pv->fundic_height_cm ?? '-' }} cm · FHT {{ $pv->fetal_heart_tone_bpm ?? '-' }} bpm</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($linkedPostnatal !== null)
            <div class="space-y-2">
                <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Postpartum record</p>
                <div class="rounded-lg border p-3 text-sm" style="border-color: var(--border); background: var(--bg);">
                    <div class="flex items-center justify-between">
                        <span class="font-medium" style="color: var(--ink);">
                            {{ \App\Models\PostnatalRecord::OUTCOMES[$linkedPostnatal->pregnancy_outcome] ?? $linkedPostnatal->pregnancy_outcome }}
                            · {{ \Carbon\Carbon::parse($linkedPostnatal->delivery_date)->format('M d, Y') }}
                        </span>
                        <span class="text-[11px]" style="color: var(--ink-muted);">
                            {{ $linkedPostnatal->postpartum_24h_date ? '24h ✓' : '' }}
                            {{ $linkedPostnatal->postpartum_7d_date ? '7d ✓' : '' }}
                            {{ $linkedPostnatal->postpartum_14d_date ? '14d ✓' : '' }}
                            {{ $linkedPostnatal->postpartum_28d_date ? '28d ✓' : '' }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        @if ($linkedFpVisits->isNotEmpty())
            <div class="space-y-2">
                <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Family planning visits</p>
                <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                    @foreach ($linkedFpVisits as $fp)
                        <div class="rounded-lg border px-3 py-2 text-sm flex items-center justify-between" style="border-color: var(--border); background: var(--bg);">
                            <span class="font-medium" style="color: var(--ink);">{{ \Carbon\Carbon::parse($fp->visit_date)->format('M d, Y') }}</span>
                            <span class="text-[11px]" style="color: var(--ink-muted);">{{ $fp->method }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>