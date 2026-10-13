@php
    $fieldPrefix = $fieldPrefix ?? '';
    $required = $required ?? false;
    $values = $values ?? null;
    $alpineModels = $alpineModels ?? null;

    $requiredMark = $required ? ' <span class="text-[var(--danger)]" aria-hidden="true">*</span>' : '';
    $requiredAttr = $required ? ' required' : '';

    $fieldValue = function (string $key) use ($values) {
        if (is_array($values)) {
            return $values[$key] ?? '';
        }
        if (is_object($values)) {
            return $values->{$key} ?? '';
        }

        return '';
    };
    $valueAttr = function (string $key) use ($alpineModels, $fieldValue) {
        if ($alpineModels !== null) {
            return '';
        }

        return ' value="' . e((string) $fieldValue($key)) . '"';
    };
    $xModelAttr = function (string $key) use ($alpineModels) {
        if ($alpineModels === null || empty($alpineModels[$key])) {
            return '';
        }

        return ' x-model="' . e($alpineModels[$key]) . '"';
    };

    $inputClass = 'w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue';
@endphp

<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
    <div class="col-span-2">
        <span class="mb-1 block text-xs font-medium text-ink-muted" id="{{ $fieldPrefix }}bp_label">BP (mmHg){!! $requiredMark !!}</span>
        <div class="flex items-center gap-2">
            <div class="flex-1">
                <input type="number" name="bp_systolic" id="{{ $fieldPrefix }}bp_systolic"
                       min="0" max="300" step="1" inputmode="numeric" placeholder="120"{{ $requiredAttr }}
                       {!! $valueAttr('bp_systolic') !!}{!! $xModelAttr('bp_systolic') !!}
                       aria-labelledby="{{ $fieldPrefix }}bp_label"
                       class="{{ $inputClass }} text-center"
                       @if (! $alpineModels) aria-describedby="{{ $fieldPrefix }}bp_systolic_hint" @endif>
                <p id="{{ $fieldPrefix }}bp_systolic_hint" class="mt-1 text-center text-[11px] text-ink-muted">Systolic</p>
            </div>
            <span class="shrink-0 font-medium text-ink-subtle" aria-hidden="true">/</span>
            <div class="flex-1">
                <input type="number" name="bp_diastolic" id="{{ $fieldPrefix }}bp_diastolic"
                       min="0" max="200" step="1" inputmode="numeric" placeholder="80"{{ $requiredAttr }}
                       {!! $valueAttr('bp_diastolic') !!}{!! $xModelAttr('bp_diastolic') !!}
                       aria-labelledby="{{ $fieldPrefix }}bp_label"
                       class="{{ $inputClass }} text-center"
                       @if (! $alpineModels) aria-describedby="{{ $fieldPrefix }}bp_diastolic_hint" @endif>
                <p id="{{ $fieldPrefix }}bp_diastolic_hint" class="mt-1 text-center text-[11px] text-ink-muted">Diastolic</p>
            </div>
        </div>
        @error('bp_systolic')
            <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p>
        @enderror
        @error('bp_diastolic')
            <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $fieldPrefix }}weight" class="mb-1 block text-xs font-medium text-ink-muted">Weight (kg){!! $requiredMark !!}</label>
        <input type="number" name="weight" id="{{ $fieldPrefix }}weight" step="0.1" min="0" max="500"
               inputmode="decimal" placeholder="60"{{ $requiredAttr }}
               {!! $valueAttr('weight') !!}{!! $xModelAttr('weight') !!}
               class="{{ $inputClass }} text-center">
        @error('weight')
            <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $fieldPrefix }}height" class="mb-1 block text-xs font-medium text-ink-muted">Height (cm){!! $requiredMark !!}</label>
        <input type="number" name="height" id="{{ $fieldPrefix }}height" step="0.1" min="0" max="300"
               inputmode="decimal" placeholder="165"{{ $requiredAttr }}
               {!! $valueAttr('height') !!}{!! $xModelAttr('height') !!}
               class="{{ $inputClass }} text-center">
        @error('height')
            <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $fieldPrefix }}temperature" class="mb-1 block text-xs font-medium text-ink-muted">Temperature (&deg;C){!! $requiredMark !!}</label>
        <input type="number" name="temperature" id="{{ $fieldPrefix }}temperature" step="0.1" min="30" max="45"
               inputmode="decimal" placeholder="36.5"{{ $requiredAttr }}
               {!! $valueAttr('temperature') !!}{!! $xModelAttr('temperature') !!}
               class="{{ $inputClass }} text-center">
        @error('temperature')
            <p class="mt-1 text-xs text-[var(--danger)]">{{ $message }}</p>
        @enderror
    </div>
</div>
