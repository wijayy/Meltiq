@props([
    'sidebar' => false,
])

<a
    {{ $attributes->class([
        'grid w-full grid-cols-[3rem_1fr_3rem] items-center gap-2 rounded-lg px-1 py-1.5',
        'transition-colors ',
    ]) }}>
    <span class="flex size-12 items-center justify-center overflow-hidden rounded-md">
        <img class="h-full w-full object-contain p-1" src="{{ asset('assets/logo/primakara.jpg') }}"
            alt="Logo Universitas Primakara">
    </span>

    <span class="min-w-0 text-center text-sm font-bold leading-tight text-mine-400 dark:text-mine-100">
        {{ config('app.name', 'Meltiq CIS') }}
    </span>

    <span class="flex size-12 items-center justify-center overflow-hidden rounded-md bg-mine-100">
        <x-app-logo-picture />
    </span>
</a>
