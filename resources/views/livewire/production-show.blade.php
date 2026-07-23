<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>
    <flux:sidebar-header>
        {{ $title }}
        <x-slot name="button"><flux:button :href="route('productions.index')" wire:navigate icon="arrow-left" variant="ghost" size="sm">Kembali</flux:button></x-slot>
    </flux:sidebar-header>
    <flux:sidebar-content>
        <div class="grid gap-4 md:grid-cols-4">
            <div><flux:text>Nomor Produksi</flux:text><flux:heading>{{ $production->production_no }}</flux:heading></div>
            <div><flux:text>Tanggal</flux:text><flux:heading>{{ $production->production_date->format('d/m/Y') }}</flux:heading></div>
            <div><flux:text>Dibuat Oleh</flux:text><flux:heading>{{ $production->creator->name }}</flux:heading></div>
            <div><flux:text>Total Qty</flux:text><flux:heading>{{ number_format($production->details->sum('qty')) }} Pcs</flux:heading></div>
        </div>
        <div><flux:text>Catatan</flux:text><div class="mt-1 rounded-lg border border-mine-200 p-3 dark:border-mine-400">{{ $production->notes ?: '—' }}</div></div>
        <flux:heading size="lg" class="pt-4 text-mine-400 dark:text-mine-100">Detail Produk</flux:heading>
        <div class="flex min-w-3xl gap-4 text-sm font-semibold"><div class="w-10">#</div><div class="w-1/2">Produk</div><div class="w-1/4">SKU</div><div class="w-1/4 text-right">Jumlah</div></div>
        @foreach ($production->details as $index => $detail)
            <div class="flex min-w-3xl gap-4 border-t border-mine-200 pt-2 text-sm dark:border-mine-400"><div class="w-10">{{ $index + 1 }}</div><div class="w-1/2 font-semibold">{{ $detail->product->name }}</div><div class="w-1/4">{{ $detail->product->sku }}</div><div class="w-1/4 text-right font-semibold">{{ number_format($detail->qty) }} Pcs</div></div>
        @endforeach
    </flux:sidebar-content>
</div>
