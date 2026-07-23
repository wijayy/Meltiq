<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}
        <x-slot name="button">
            <div class="text-sm text-mine-300 dark:text-mine-100">{{ now()->translatedFormat('l, d F Y') }}</div>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Produk Aktif', 'value' => $this->metrics['products'], 'icon' => 'archive-box', 'href' => route('products.index')],
                ['label' => 'Lokasi Aktif', 'value' => $this->metrics['locations'], 'icon' => 'map-pin', 'href' => route('locations.index')],
                ['label' => 'Total Stok', 'value' => number_format($this->metrics['total_stock']).' Pcs', 'icon' => 'circle-stack', 'href' => route('stocks.index')],
                ['label' => 'Pergerakan Hari Ini', 'value' => $this->metrics['movements_today'], 'icon' => 'arrows-right-left', 'href' => route('stock-movements.index')],
                ['label' => 'Pengguna', 'value' => $this->metrics['users'], 'icon' => 'users', 'href' => route('users.index')],
            ] as $metric)
                <a href="{{ $metric['href'] }}" wire:navigate
                    class="group rounded-xl border border-mine-200 bg-white p-4 transition hover:border-mine-300 hover:shadow-sm dark:border-mine-400 dark:bg-neutral-700">
                    <div class="flex items-center justify-between">
                        <flux:icon :name="$metric['icon']" class="size-5 text-mine-300 dark:text-mine-100" />
                        <flux:icon.arrow-up-right class="size-4 text-zinc-400 transition group-hover:text-mine-300" />
                    </div>
                    <div class="mt-4 text-2xl font-bold text-mine-400 dark:text-mine-100">{{ $metric['value'] }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-300">{{ $metric['label'] }}</div>
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <section class="rounded-xl border border-mine-200 bg-white p-5 dark:border-mine-400 dark:bg-neutral-700">
                <flux:heading size="lg" class="text-mine-400 dark:text-mine-100">Distribusi Stok</flux:heading>
                <flux:text class="mt-1">Stok saat ini berdasarkan tipe lokasi.</flux:text>
                <div class="mt-5 space-y-4">
                    @foreach ([
                        ['label' => 'Gudang', 'value' => $this->stockByLocationType['warehouse'], 'color' => 'bg-mine-400'],
                        ['label' => 'Outlet', 'value' => $this->stockByLocationType['outlet'], 'color' => 'bg-mine-300'],
                        ['label' => 'Kedaluwarsa', 'value' => $this->stockByLocationType['virtual'], 'color' => 'bg-red-500'],
                    ] as $stock)
                        @php($percentage = $this->metrics['total_stock'] > 0 ? ($stock['value'] / $this->metrics['total_stock']) * 100 : 0)
                        <div>
                            <div class="mb-1 flex justify-between text-sm"><span>{{ $stock['label'] }}</span><span class="font-semibold">{{ number_format($stock['value']) }} Pcs</span></div>
                            <div class="h-2 overflow-hidden rounded-full bg-mine-100 dark:bg-neutral-600"><div class="h-full rounded-full {{ $stock['color'] }}" style="width: {{ $percentage }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-mine-200 bg-white p-5 dark:border-mine-400 dark:bg-neutral-700 xl:col-span-2">
                <div class="flex items-center justify-between">
                    <div><flux:heading size="lg" class="text-mine-400 dark:text-mine-100">Stok Menipis</flux:heading><flux:text class="mt-1">Kombinasi produk dan lokasi dengan stok 1–10 Pcs.</flux:text></div>
                    <flux:button :href="route('stocks.index')" wire:navigate variant="ghost" size="sm">Lihat Stok</flux:button>
                </div>
                <div class="mt-4 grid gap-2 md:grid-cols-2">
                    @forelse ($this->lowStocks as $stock)
                        <div class="flex items-center justify-between rounded-lg border border-mine-100 p-3 dark:border-mine-400">
                            <div><div class="font-semibold">{{ $stock->product->name }}</div><div class="text-xs text-zinc-500">{{ $stock->location->name }} · {{ $stock->product->sku }}</div></div>
                            <flux:badge color="red">{{ number_format($stock->stock) }} Pcs</flux:badge>
                        </div>
                    @empty
                        <div class="py-8 text-center text-sm text-zinc-500 md:col-span-2">Tidak ada stok menipis.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-xl border border-mine-200 bg-white p-5 dark:border-mine-400 dark:bg-neutral-700">
                <div class="mb-4 flex items-center justify-between"><flux:heading size="lg" class="text-mine-400 dark:text-mine-100">Produksi Terbaru</flux:heading><flux:button :href="route('productions.index')" wire:navigate variant="ghost" size="sm">Lihat Semua</flux:button></div>
                <div class="space-y-2">
                    @forelse ($this->recentProductions as $production)
                        <a href="{{ route('productions.show', $production) }}" wire:navigate class="flex items-center justify-between rounded-lg border-t border-mine-100 py-3 text-sm hover:text-mine-300 dark:border-mine-400">
                            <div><div class="font-semibold">{{ $production->production_no }}</div><div class="text-xs text-zinc-500">{{ $production->production_date->format('d/m/Y') }} · {{ $production->creator->name }}</div></div>
                            <div class="font-semibold">{{ number_format($production->details_sum_qty ?? 0) }} Pcs</div>
                        </a>
                    @empty
                        <div class="py-8 text-center text-sm text-zinc-500">Belum ada produksi.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl border border-mine-200 bg-white p-5 dark:border-mine-400 dark:bg-neutral-700">
                <div class="mb-4 flex items-center justify-between"><flux:heading size="lg" class="text-mine-400 dark:text-mine-100">Pengiriman Terbaru</flux:heading><flux:button :href="route('visits.index')" wire:navigate variant="ghost" size="sm">Lihat Semua</flux:button></div>
                <div class="space-y-2">
                    @forelse ($this->recentVisits as $visit)
                        <a href="{{ route('visits.show', $visit) }}" wire:navigate class="flex items-center justify-between rounded-lg border-t border-mine-100 py-3 text-sm hover:text-mine-300 dark:border-mine-400">
                            <div><div class="font-semibold">{{ $visit->visit_no }}</div><div class="text-xs text-zinc-500">{{ $visit->visit_date->format('d/m/Y') }} · {{ $visit->location->name }}</div></div>
                            <flux:badge color="zinc">{{ $visit->details_count }} Produk</flux:badge>
                        </a>
                    @empty
                        <div class="py-8 text-center text-sm text-zinc-500">Belum ada kunjungan.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </flux:sidebar-content>
</div>
