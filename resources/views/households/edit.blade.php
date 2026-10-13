@extends('layouts.app')

@section('title', 'Edit Household')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <!-- Header -->
    <x-page-header title="Edit Household" :subtitle="__('Update household information and contact details.')">
        <x-slot:actions>
            <x-btn href="{{ route('households.index') }}" variant="outlined">
                Back to Households
            </x-btn>
        </x-slot:actions>
    </x-page-header>

    <!-- Form -->
    <div class="rounded-2xl border overflow-hidden"
         style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <form method="POST" action="{{ route('households.update', $household->id) }}" class="space-y-5 p-4 lg:p-6">
            @csrf
            @method('PUT')

            <!-- Zone Dropdown -->
            <x-field name="zone_id" label="Zone" :required="true">
                <x-slot:control>
                    <select id="zone_id" name="zone_id" required
                            class="w-full px-3 py-2 rounded-lg border text-sm"
                            style="border-color: var(--border); background: var(--bg-surface);">
                        <option value="">Select a zone</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" {{ $household->zone_id == $zone->id ? 'selected' : '' }}>
                                Zone {{ $zone->zone_number }}
                            </option>
                        @endforeach
                    </select>
                </x-slot:control>
            </x-field>

            <!-- Head of Household Name Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <x-field name="head_first_name" label="First Name" :required="true">
                    <x-slot:control>
                        <input type="text" id="head_first_name" name="head_first_name"
                               value="{{ old('head_first_name', $household->head_first_name) }}"
                               required maxlength="255"
                               class="w-full px-3 py-2 rounded-lg border text-sm"
                               style="border-color: var(--border); background: var(--bg-surface);">
                    </x-slot:control>
                </x-field>

                <x-field name="head_middle_name" label="Middle Name" :required="false">
                    <x-slot:control>
                        <input type="text" id="head_middle_name" name="head_middle_name"
                               value="{{ old('head_middle_name', $household->head_middle_name) }}"
                               maxlength="255"
                               class="w-full px-3 py-2 rounded-lg border text-sm"
                               style="border-color: var(--border); background: var(--bg-surface);">
                    </x-slot:control>
                </x-field>

                <x-field name="head_last_name" label="Last Name (Family Name)" :required="true">
                    <x-slot:control>
                        <input type="text" id="head_last_name" name="head_last_name"
                               value="{{ old('head_last_name', $household->head_last_name) }}"
                               required maxlength="255"
                               class="w-full px-3 py-2 rounded-lg border text-sm"
                               style="border-color: var(--border); background: var(--bg-surface);">
                    </x-slot:control>
                </x-field>

                <x-field name="head_suffix" label="Suffix" :required="false">
                    <x-slot:control>
                        <input type="text" id="head_suffix" name="head_suffix"
                               value="{{ old('head_suffix', $household->head_suffix) }}"
                               maxlength="255" placeholder="Jr., Sr., III"
                               class="w-full px-3 py-2 rounded-lg border text-sm"
                               style="border-color: var(--border); background: var(--bg-surface);">
                    </x-slot:control>
                </x-field>
            </div>

            <!-- Contact Number -->
            <x-field name="contact_number" label="Contact Number (Optional)" :required="false" help="Allowed: digits, spaces, +, - and ()">
                <x-slot:control>
                    <input type="text" id="contact_number" name="contact_number"
                           value="{{ old('contact_number', $household->contact_number) }}"
                           maxlength="32" placeholder="e.g., +63-9171234567"
                           class="w-full px-3 py-2 rounded-lg border text-sm"
                           style="border-color: var(--border); background: var(--bg-surface);">
                </x-slot:control>
            </x-field>

            <!-- Action Buttons -->
            <div class="flex gap-3 justify-end pt-4 border-t" style="border-color: var(--border);">
                <x-btn href="{{ route('households.index') }}" variant="outlined">
                    Cancel
                </x-btn>
                <x-btn type="submit" variant="primary">
                    Save Changes
                </x-btn>
            </div>
        </form>
    </div>
</div>
@endsection