@extends('layouts.app')

@section('title', 'Add Medicine')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <x-flash />

    <x-page-header
        title="Add medicine"
        subtitle="Add a new medicine to the system."
        icon="fa-solid fa-pills"
    >
        <x-slot name="actions">
            <a href="{{ route('medicines.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium text-ink transition hover:bg-black/5">Back to medicines</a>
        </x-slot>
    </x-page-header>

    <x-card class="w-full max-w-xl">
        <form action="{{ route('medicines.store') }}" method="POST" class="space-y-4">
            @csrf
            <x-field label="Medicine name" name="name" id="name" required :value="old('name')" help="e.g. Paracetamol 500mg">
                <x-slot name="control">
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                </x-slot>
            </x-field>

            <x-field label="Form" name="form" id="form" :value="old('form')" help="e.g. Tablet, Capsule, Syrup, Injection">
                <x-slot name="control">
                    <input type="text" name="form" id="form" value="{{ old('form') }}"
                        class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                </x-slot>
            </x-field>

            <x-btn type="submit" icon="fa-solid fa-plus" class="w-full sm:w-auto">
                Add medicine
            </x-btn>
        </form>
    </x-card>
</div>
@endsection