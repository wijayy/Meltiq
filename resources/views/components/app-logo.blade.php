@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="Meltiq CIS" {{ $attributes }}>
        <x-slot name="logo"
            class="flex aspect-square size-12 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-picture />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Meltiq CIS" {{ $attributes }}>
        <x-slot name="logo"
            class="flex aspect-square size-12 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-picture />
        </x-slot>
    </flux:brand>
@endif
