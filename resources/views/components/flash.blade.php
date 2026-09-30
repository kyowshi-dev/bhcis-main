@props([
    'dismissible' => false,
])

@php
    $success = session('success');
    $error = session('error');
    $errorsAny = $errors->any();
@endphp

@if ($success || $error || $errorsAny)
    <div class="mb-3 space-y-2" role="region" aria-label="Status messages">
        @if ($success)
            <div class="flex items-start gap-3 rounded-xl border border-primary/25 bg-teal-soft px-4 py-3 text-sm" role="status">
                <i class="fa-solid fa-circle-check mt-0.5 text-primary" aria-hidden="true"></i>
                <p class="font-medium text-ink">{{ $success }}</p>
            </div>
        @endif

        @if ($error)
            <div class="flex items-start gap-3 rounded-xl border border-danger/30 bg-danger-soft px-4 py-3 text-sm" role="alert">
                <i class="fa-solid fa-circle-exclamation mt-0.5 text-danger" aria-hidden="true"></i>
                <p class="font-medium text-ink">{{ $error }}</p>
            </div>
        @endif

        @if ($errorsAny)
            <div class="flex items-start gap-3 rounded-xl border border-danger/30 bg-danger-soft px-4 py-3 text-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 text-danger" aria-hidden="true"></i>
                <div class="min-w-0">
                    <p class="font-semibold text-ink">Please review the highlighted fields.</p>
                    <ul class="mt-1 list-disc pl-5 text-ink-muted">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif
