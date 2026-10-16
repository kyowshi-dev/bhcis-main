@props([
    'action' => '',
    'message' => 'Are you sure?',
    'confirmText' => 'Delete',
    'cancelText' => 'Cancel',
    'method' => 'DELETE',
    'class' => '',
])

@php
    $confirmId = 'confirm-' . uniqid();
@endphp

<form action="{{ $action }}" method="POST" id="{{ $confirmId }}" class="{{ $class }}" data-message="{{ $message }}" data-confirm-text="{{ $confirmText }}">
    @csrf
    @method($method)
    {{ $slot }}
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('{{ $confirmId }}');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: form.dataset.message || 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--danger)',
                    cancelButtonColor: 'var(--ink-muted)',
                    confirmButtonText: form.dataset.confirmText || 'Delete',
                    cancelButtonText: '{{ $cancelText }}',
                }).then(result => { if (result.isConfirmed) form.submit(); });
            });
        }
    });
</script>
@endpush