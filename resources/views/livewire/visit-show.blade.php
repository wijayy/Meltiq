<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>
    <flux:sidebar-header>
        {{ $title }}
        <x-slot name="button"><flux:button :href="route('visits.index')" wire:navigate icon="arrow-left" variant="ghost" size="sm">Kembali</flux:button></x-slot>
    </flux:sidebar-header>
    <flux:sidebar-content>
        <div class="grid gap-4 md:grid-cols-4">
            <div><flux:text>Nomor Pengiriman</flux:text><flux:heading>{{ $visit->visit_no }}</flux:heading></div>
            <div><flux:text>Tanggal</flux:text><flux:heading>{{ $visit->visit_date->format('d/m/Y') }}</flux:heading></div>
            <div><flux:text>Outlet</flux:text><flux:heading>{{ $visit->location->name }}</flux:heading></div>
            <div><flux:text>Dibuat Oleh</flux:text><flux:heading>{{ $visit->creator->name }}</flux:heading></div>
        </div>
        <div><flux:text>Catatan</flux:text><div class="mt-1 rounded-lg border border-mine-200 p-3 dark:border-mine-400">{{ $visit->notes ?: '—' }}</div></div>
        <flux:heading size="lg" class="pt-4 text-mine-400 dark:text-mine-100">Detail Stok</flux:heading>
        <div class="flex min-w-7xl gap-3 text-sm font-semibold"><div class="w-8">#</div><div class="min-w-64 flex-1">Produk</div><div class="w-28 text-right">Sebelum</div><div class="w-28 text-right">Fisik</div><div class="w-28 text-right">Terjual</div><div class="w-28 text-right">Dikembalikan</div><div class="w-28 text-right">Kedaluwarsa</div><div class="w-28 text-right">Pengiriman</div><div class="w-28 text-right">Stok Akhir</div></div>
        @foreach ($visit->details as $index => $detail)
            @php($sold = $detail->stockBefore - $detail->physicalStock - $detail->expiredQty)
            @php($finalStock = $detail->physicalStock - $detail->returnedQty + $detail->newDeliveryQty)
            <div class="flex min-w-7xl gap-3 border-t border-mine-200 pt-2 text-sm dark:border-mine-400"><div class="w-8">{{ $index + 1 }}</div><div class="min-w-64 flex-1"><div class="font-semibold">{{ $detail->product->name }}</div><div class="text-xs text-zinc-500">{{ $detail->product->sku }}</div></div><div class="w-28 text-right">{{ number_format($detail->stockBefore) }}</div><div class="w-28 text-right">{{ number_format($detail->physicalStock) }}</div><div class="w-28 text-right">{{ number_format($sold) }}</div><div class="w-28 text-right">{{ number_format($detail->returnedQty) }}</div><div class="w-28 text-right">{{ number_format($detail->expiredQty) }}</div><div class="w-28 text-right">{{ number_format($detail->newDeliveryQty) }}</div><div class="w-28 text-right font-semibold">{{ number_format($finalStock) }}</div></div>
        @endforeach
    </flux:sidebar-content>
</div>
