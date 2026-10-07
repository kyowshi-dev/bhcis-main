import './bootstrap';
import Swal from 'sweetalert2';
import '@fortawesome/fontawesome-free/css/all.min.css';

window.Swal = Swal;

window.modalMerge = (extra) => ({ open: false, ...(extra ?? {}) });

// Immunization schedule table: variant selection, dose checkboxes, batch admin.
// Defined here (not inline) because the table also arrives via AJAX fragments
// whose <script> tags never execute.
window.scheduleTable = (config) => ({
    selectedKeys: [],
    activeVariants: { ...(config.groupDefaults || {}) },
    config,
    get eligibleKeys() {
        return (this.config.rowKeys || []).filter((key) => this.isEligible(key));
    },
    get allSelected() {
        const eligible = this.eligibleKeys;
        return eligible.length > 0 && eligible.every((key) => this.selectedKeys.includes(key));
    },
    get someSelected() {
        return this.selectedKeys.length > 0;
    },
    activeId(key) {
        return this.activeVariants[key] !== undefined ? this.activeVariants[key] : key;
    },
    isEligible(key) {
        const state = this.config.elig[this.activeId(key)];
        return state === 'overdue_allowed' || state === 'out_of_window';
    },
    isActive(key, id) {
        return String(this.activeId(key)) === String(id);
    },
    onVariantChange(key, event) {
        this.activeVariants[key] = event.target.value;
        if (!this.isEligible(key)) {
            this.selectedKeys = this.selectedKeys.filter((k) => k !== key);
        }
    },
    toggleAll() {
        this.selectedKeys = this.allSelected ? [] : this.eligibleKeys.slice();
    },
    openBatch() {
        const doses = this.selectedKeys
            .map((key) => {
                const id = this.activeId(key);
                return {
                    id: Number(id),
                    name: this.config.names[id] || '',
                    doseNumber: this.config.doseNumbers[id] ?? null,
                    outOfWindow: this.config.elig[id] === 'out_of_window',
                };
            })
            .filter((dose) => Number.isFinite(dose.id));

        if (doses.length === 0) return;

        this.$dispatch('open-administer', { batch: true, doses });
    },
});

window.administerVaccine = () => ({
    adminOpen: false,
    mode: 'single',
    vaccineId: null,
    vaccineName: '',
    doseNumber: null,
    batchDoses: [],
    requiresTemp: false,
    outOfWindow: false,
    init() {
        this.$watch('adminOpen', (open) => {
            document.body.classList.toggle('overflow-hidden', open);
        });
    },
    initReopen(hasErrors, batchDoses) {
        if (!hasErrors) return;
        if (Array.isArray(batchDoses) && batchDoses.length) {
            this.setBatch(batchDoses, batchDoses.some((dose) => dose.outOfWindow));
            return;
        }
        this.mode = 'single';
        this.batchDoses = [];
        this.vaccineId = Number(document.querySelector('input[name="vaccine_id"]')?.value) || null;
        this.vaccineName = '';
        this.requiresTemp = false;
        this.outOfWindow = false;
        this.adminOpen = true;
    },
    openAdminister(detail) {
        if (detail.batch && Array.isArray(detail.doses)) {
            this.setBatch(detail.doses, detail.doses.some((dose) => dose.outOfWindow));
            return;
        }
        this.mode = 'single';
        this.batchDoses = [];
        this.vaccineId = detail.vaccineId ?? null;
        this.vaccineName = detail.vaccineName ?? '';
        this.doseNumber = detail.doseNumber ?? null;
        this.requiresTemp = Boolean(detail.requiresTemp);
        this.outOfWindow = Boolean(detail.outOfWindow);
        this.adminOpen = true;
        this.$nextTick(() => document.getElementById('administer_date')?.focus());
    },
    setBatch(doses, outOfWindow) {
        this.mode = 'batch';
        this.batchDoses = doses;
        this.vaccineId = null;
        this.vaccineName = '';
        this.doseNumber = null;
        this.requiresTemp = false;
        this.outOfWindow = Boolean(outOfWindow);
        this.adminOpen = true;
        this.$nextTick(() => document.getElementById('administer_date')?.focus());
    },
});

document.addEventListener('alpine:init', () => {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.store('modals', {
        page: false,
        consultation: false,
        printReferral: false,
        outward: false,
        diagnosis: false,
        prescription: false,
    });
});

// --- Global form-submit loading overlay -------------------------------------
// Every POST/PUT/PATCH/DELETE form gets a blocking "Saving" overlay while the
// synchronous round-trip is in flight, so users get feedback instead of a
// frozen page. Forms that answer with a file download instead of a page
// navigation must opt out with the data-no-loading attribute.
(function () {
    const SUBMIT_METHODS = ['post', 'put', 'patch', 'delete'];
    const OVERLAY_DELAY_MS = 150;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let overlayEl = null;

    function shouldSkip(form) {
        if (!(form instanceof HTMLFormElement)) {
            return true;
        }
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (!SUBMIT_METHODS.includes(method)) {
            return true;
        }
        if (form.hasAttribute('data-no-loading') || form.target === '_blank') {
            return true;
        }
        return overlayEl !== null;
    }

    function showOverlay(form) {
        if (overlayEl || !form.isConnected) {
            return;
        }

        overlayEl = document.createElement('div');
        overlayEl.id = 'formSubmitOverlay';
        overlayEl.setAttribute('role', 'status');
        overlayEl.setAttribute('aria-live', 'polite');
        overlayEl.innerHTML =
            '<div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 backdrop-blur-sm">' +
                '<div class="flex flex-col items-center gap-4 rounded-2xl border border-border bg-surface-elevated px-10 py-8 shadow-xl">' +
                    '<i class="fa-solid ' + (prefersReducedMotion ? 'fa-hourglass-half' : 'fa-spinner fa-spin') + ' text-3xl" aria-hidden="true" style="color: var(--primary);"></i>' +
                    '<p class="text-sm font-semibold" style="color: var(--ink);">Saving, please wait...</p>' +
                '</div>' +
            '</div>';
        document.body.appendChild(overlayEl);
        document.body.style.overflow = 'hidden';

        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.disabled = true;
        }
    }

    // User-initiated submits (button click, Enter, requestSubmit). Capture
    // phase so page-level stopPropagation cannot silence the overlay.
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (shouldSkip(form)) {
            return;
        }

        // Other handlers may still cancel this submit (fetch-based forms never
        // navigate), so wait for the dispatch to finish before showing.
        window.setTimeout(function () {
            if (event.defaultPrevented || overlayEl) {
                return;
            }
            showOverlay(form);
        }, OVERLAY_DELAY_MS);
    }, true);

    // Programmatic submits (Swal-confirm flows call form.submit(), which does
    // not dispatch a submit event, so the listener above never fires).
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (!shouldSkip(this)) {
            showOverlay(this);
        }
        return nativeSubmit.apply(this, arguments);
    };
})();
