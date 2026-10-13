<div class="p-4 space-y-3">
    <h2 class="font-semibold text-sm" style="color: var(--ink);">Actions</h2>

    @if ($clinicalReviewOpen)
        <div class="space-y-2">
            <div class="rounded-lg p-3 text-center" :class="diagnosisCount === 0 ? 'bg-amber-soft' : (prescriptionCount > 0 ? 'bg-teal-soft' : 'bg-blue-soft')" style="border-color: var(--border);">
                <div class="flex items-center justify-center gap-3 text-xs mb-2">
                    <span class="px-2 py-0.5 rounded-full" :class="diagnosisCount > 0 ? 'bg-[var(--primary)] text-white' : 'bg-[var(--border)] text-[var(--ink-muted)]'" x-text="diagnosisCount + ' Dx'"></span>
                    <span class="px-2 py-0.5 rounded-full" :class="prescriptionCount > 0 ? 'bg-[var(--primary)] text-white' : 'bg-[var(--border)] text-[var(--ink-muted)]'" x-text="prescriptionCount + ' Rx'"></span>
                </div>
                <p class="text-[11px]" style="color: var(--ink-muted);" x-show="diagnosisCount === 0 && prescriptionCount === 0">Add a diagnosis to enable finalize</p>
                <p class="text-[11px]" style="color: var(--ink-muted);" x-show="diagnosisCount > 0 && prescriptionCount === 0">Diagnosis recorded. Add Rx or finalize.</p>
                <p class="text-[11px]" style="color: var(--ink-muted);" x-show="diagnosisCount === 0 && prescriptionCount > 0">Prescription without diagnosis - add Dx to enable finalize.</p>
                <p class="text-[11px]" style="color: var(--ink-muted);" x-show="diagnosisCount > 0 && prescriptionCount > 0">Both recorded. Ready to finalize.</p>
            </div>

            <form id="finalizeForm" action="{{ route('consultations.finalize', $consultation->id) }}" method="POST">
                @csrf
                <input id="outward_refer_to_higher_facility" type="hidden" name="refer_to_higher_facility" value="0">
                <input id="outward_hidden_referred_to" type="hidden" name="referred_to" value="">
                <input id="outward_hidden_referral_reason_details" type="hidden" name="referral_reason_details" value="">
                <input id="outward_hidden_pertinent_history" type="hidden" name="pertinent_history" value="">
                <input id="outward_hidden_actions_taken" type="hidden" name="actions_taken" value="">
                <div id="outward_hidden_referral_reasons"></div>

                <button type="submit" id="finalizeBtn" form="finalizeForm" class="w-full rounded-xl bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 flex items-center justify-center gap-2" :disabled="diagnosisCount === 0" :class="{ 'opacity-50 cursor-not-allowed': diagnosisCount === 0 }" title="F - Finalize (when ready)">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Complete consultation
                </button>
            </form>

            @if ($canReferExternally)
                <button type="button" onclick="openConsultationOutwardReferralWizard()" class="w-full rounded-xl border border-[var(--primary)] px-4 py-2.5 text-sm font-semibold text-[var(--primary)] transition hover:bg-teal-soft flex items-center justify-center gap-2" title="R - Refer">
                    <i class="fa-solid fa-hospital" aria-hidden="true"></i> Refer to higher facility
                </button>
            @else
                <p class="text-center text-[11px]" style="color: var(--ink-muted);">Only Nurse and Doctor roles can trigger external referral.</p>
            @endif
        </div>
    @elseif ($consultation->status === \App\Enums\ConsultationStatus::NurseReview->value && $canReferExternally)
        <form id="consultationShowReferralForm" action="{{ route('consultations.refer', $consultation->id) }}" method="POST" class="hidden">
            @csrf
            <input id="outward_refer_to_higher_facility" type="hidden" name="refer_to_higher_facility" value="1">
            <input id="outward_hidden_referred_to" type="hidden" name="referred_to" value="">
            <input id="outward_hidden_referral_reason_details" type="hidden" name="referral_reason_details" value="">
            <input id="outward_hidden_pertinent_history" type="hidden" name="pertinent_history" value="">
            <input id="outward_hidden_actions_taken" type="hidden" name="actions_taken" value="">
            <div id="outward_hidden_referral_reasons"></div>
        </form>

        <button type="button" onclick="openConsultationOutwardReferralWizard()" class="w-full rounded-xl border border-[var(--primary)] px-4 py-2.5 text-sm font-semibold text-[var(--primary)] transition hover:bg-teal-soft flex items-center justify-center gap-2" title="R - Refer">
            <i class="fa-solid fa-hospital" aria-hidden="true"></i> Refer to higher facility
        </button>
        <p class="text-center text-[11px]" style="color: var(--ink-muted);">Nurse review in progress</p>
    @else
        <p class="text-center text-sm py-4" style="color: var(--ink-muted);">No actions available</p>
    @endif

    <div class="border-t pt-3" style="border-color: var(--border);">
        <p class="text-[10px] font-semibold uppercase tracking-wide mb-2" style="color: var(--ink-muted);">Keyboard Shortcuts</p>
        <div class="space-y-1.5 text-[11px]" style="color: var(--ink-muted);">
            <div class="flex justify-between"><kbd class="px-1.5 py-0.5 rounded text-[10px] font-mono" style="background: var(--border);">D</kbd><span>Add Diagnosis</span></div>
            <div class="flex justify-between"><kbd class="px-1.5 py-0.5 rounded text-[10px] font-mono" style="background: var(--border);">P</kbd><span>Add Prescription</span></div>
            <div class="flex justify-between"><kbd class="px-1.5 py-0.5 rounded text-[10px] font-mono" style="background: var(--border);">V</kbd><span>Re-take Vitals</span></div>
            <div class="flex justify-between"><kbd class="px-1.5 py-0.5 rounded text-[10px] font-mono" style="background: var(--border);">F</kbd><span>Finalize</span></div>
            <div class="flex justify-between"><kbd class="px-1.5 py-0.5 rounded text-[10px] font-mono" style="background: var(--border);">R</kbd><span>Refer Patient</span></div>
            <div class="flex justify-between"><kbd class="px-1.5 py-0.5 rounded text-[10px] font-mono" style="background: var(--border);">Esc</kbd><span>Close Modals</span></div>
        </div>
    </div>
</div>
