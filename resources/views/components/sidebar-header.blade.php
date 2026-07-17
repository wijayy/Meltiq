<?php

use Livewire\Component;

new class extends Component {
    //
};
?>


<div class="bg-white dark:bg-neutral-700 py-2 px-4 flex justify-between items-center">
    <div class="">
        <h1 class="text-lg font-bold text-mine-200 dark:text-mine-100">{{ $title ?? '' }}</h1>
        @if (session()->has('success'))
            <div class="text-green-500 text-sm">{{ session('success') }}</div>
        @endif
        @if (session()->has('error'))
            <div class="text-pink-500 text-sm">{{ session('error') }}</div>
        @endif
    </div>
    <div class="flex gap-2">
        {{ $button ?? '' }}
    </div>
</div>
