<header class="flex shrink-0 items-center justify-between bg-white px-4 py-2 dark:bg-neutral-700">
    <div class="">
        <h1 class="text-lg font-bold text-mine-200 dark:text-mine-100">{{ $slot }}</h1>
        @if (session()->has('success'))
            <div class="text-sm text-green-500" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="text-sm text-red-500" role="alert">
                {{ session('error') }}
            </div>
        @endif
    </div>
    <div class="flex gap-2">
        {{ $button ?? '' }}
    </div>
</header>
