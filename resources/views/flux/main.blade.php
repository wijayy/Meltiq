@blaze(fold: true)

@props([
    'container' => null,
])

@php
    $classes = Flux::classes('[grid-area:main]')
        ->add('p-6 lg:p-4 space-y-4')
        ->add('[[data-flux-container]_&]:px-0') // If there is a wrapping container, let IT handle the x padding...
        ->add($container ? 'mx-auto w-full [:where(&)]:max-w-7xl' : '');
@endphp

<div {{ $attributes->class($classes) }} data-flux-main>
    {{ $slot }}
</div>
