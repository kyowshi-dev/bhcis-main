@extends('layouts.app')

@section('title', 'Privacy Settings')

@section('content')
<x-page-header title="Privacy Settings" subtitle="Manage privacy policy and data processing purposes" icon="fa-solid fa-shield-halved" />

<x-card class="mt-6">
    <form action="{{ route('privacy.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-field label="Privacy Policy Version" name="privacy_policy_version">
                <x-slot:control>
                    <input type="text" name="privacy_policy_version" value="{{ old('privacy_policy_version', $privacy->privacy_policy_version) }}" placeholder="e.g. 1.0" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                </x-slot:control>
            </x-field>

            <x-field label="Data Retention (days)" name="data_retention_days">
                <x-slot:control>
                    <input type="number" name="data_retention_days" value="{{ old('data_retention_days', $privacy->data_retention_days) }}" min="1" placeholder="2555" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                </x-slot:control>
            </x-field>
        </div>

        <x-field label="Privacy Policy Content" name="privacy_policy_content">
            <x-slot:control>
                <textarea name="privacy_policy_content" rows="14" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition font-mono" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">{{ old('privacy_policy_content', $privacy->privacy_policy_content) }}</textarea>
            </x-slot:control>
            <x-slot:help>HTML or Markdown. Use &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt;, or markdown syntax like # heading, **bold**, - lists.</x-slot:help>
        </x-field>

        <hr class="border-border">

        <h3 class="font-display font-semibold text-lg" style="color: var(--ink);">Data Processing Purposes</h3>

        <div id="purposes-container" class="space-y-2">
            @php
                $purposes = old('purpose_limitation', $privacy->purpose_limitation ?? []);
            @endphp
            @if (!empty($purposes))
                @foreach ($purposes as $index => $purpose)
                    <div class="flex items-center gap-2">
                        <input type="text" name="purpose_limitation[]" value="{{ $purpose }}" class="flex-1 rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                        <button type="button" onclick="this.parentElement.remove()" class="p-2 rounded-lg transition" style="color: var(--accent);" title="Remove">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endforeach
            @endif
        </div>

        <button type="button" onclick="addPurpose()" class="inline-flex items-center gap-2 text-sm font-medium transition" style="color: var(--primary);">
            <i class="fa-solid fa-plus"></i> Add Purpose
        </button>

        <div class="flex justify-end pt-4 border-t border-border">
            <x-btn type="submit" variant="primary">Save Settings</x-btn>
        </div>
    </form>
</x-card>

@push('scripts')
<script>
function addPurpose() {
    const container = document.getElementById('purposes-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2';
    div.innerHTML = `
        <input type="text" name="purpose_limitation[]" class="flex-1 rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" placeholder="Enter purpose">
        <button type="button" onclick="this.parentElement.remove()" class="p-2 rounded-lg transition" style="color: var(--accent);" title="Remove">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;
    container.appendChild(div);
    div.querySelector('input').focus();
}
</script>
@endpush
@endsection
