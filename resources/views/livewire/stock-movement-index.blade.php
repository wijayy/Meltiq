<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}

        <x-slot name="button">
            <flux:button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                icon="arrow-down-tray" variant="primary" size="sm">
                <span wire:loading.remove wire:target="exportExcel">Ekspor Excel</span>
                <span wire:loading wire:target="exportExcel">Membuat Excel...</span>
            </flux:button>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <div class="flex min-w-4xl flex-wrap items-end gap-4">
            <div class="w-full sm:w-48"><flux:input wire:model.live="periodBegin" type="date" label="Awal Periode" /></div>
            <div class="w-full sm:w-48"><flux:input wire:model.live="periodEnd" type="date" label="Akhir Periode" /></div>
            <div class="w-full sm:w-64">
                <flux:select wire:model.live="productSlug" label="Produk">
                    <flux:select.option value="">Semua Produk</flux:select.option>
                    @foreach ($this->products as $product)
                        <flux:select.option wire:key="movement-product-{{ $product->id }}" value="{{ $product->slug }}">
                            {{ $product->name }} — {{ $product->sku }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-full sm:w-64">
                <flux:select wire:model.live="locationSlug" label="Lokasi">
                    <flux:select.option value="">Semua Lokasi</flux:select.option>
                    @foreach ($this->locations as $location)
                        <flux:select.option wire:key="movement-location-{{ $location->id }}" value="{{ $location->slug }}">
                            {{ $location->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="flex min-w-6xl items-center gap-4 text-sm font-semibold">
            <div class="w-10">#</div><div class="w-36">Tanggal</div><div class="w-64">Produk</div>
            <div class="w-28 text-center">Tipe</div><div class="w-48">Dari</div><div class="w-48">Ke</div>
            <div class="w-28 text-right">Qty</div><div class="w-40">Referensi</div>
        </div>

        @forelse ($this->movements as $index => $movement)
            <div wire:key="movement-{{ $movement->id }}"
                class="flex min-w-6xl items-center gap-4 border-t border-mine-200 pt-2 text-sm dark:border-mine-400">
                <div class="w-10">{{ $index + 1 }}</div>
                <div class="w-36">{{ $movement->movement_date->format('d/m/Y H:i') }}</div>
                <div class="w-64"><div class="font-semibold">{{ $movement->product->name }}</div><div class="text-xs text-zinc-500">{{ $movement->product->sku }}</div></div>
                <div class="w-28 text-center"><flux:badge color="zinc" size="sm">{{ ucfirst($movement->movement_type) }}</flux:badge></div>
                <div class="w-48">{{ $movement->fromLocation?->name ?? '—' }}</div>
                <div class="w-48">{{ $movement->toLocation?->name ?? '—' }}</div>
                <div class="w-28 text-right font-semibold">{{ number_format($movement->qty) }} Pcs</div>
                <div class="w-40">
                    @if ($movement->referenceUrl())
                        <flux:link :href="$movement->referenceUrl()" wire:navigate>{{ $movement->reference_no ?? 'Lihat Detail' }}</flux:link>
                    @else
                        {{ $movement->reference_no ?? '—' }}
                    @endif
                </div>
            </div>
        @empty
            <div class="min-w-4xl border-t border-mine-200 py-10 text-center text-sm text-mine-300 dark:border-mine-400 dark:text-mine-100">
                Pergerakan stok tidak ditemukan pada filter yang dipilih.
            </div>
        @endforelse

        <div class="pt-6">
            <flux:heading size="lg" class="text-mine-400 dark:text-mine-100">Rangkuman Pergerakan</flux:heading>
            <flux:text class="mt-1">Stok bertambah dan berkurang ditampilkan terpisah untuk setiap product dan location.</flux:text>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th rowspan="2" class="sticky left-0 min-w-64 border border-mine-200 bg-white p-3 text-left dark:border-mine-400 dark:bg-neutral-700">Produk</th>
                        @foreach ($this->summaryLocations as $location)
                            <th colspan="{{ $location->type === 'virtual' ? 1 : 2 }}" class="{{ $location->type === 'virtual' ? 'min-w-32' : 'min-w-64' }} border border-mine-200 p-3 text-center dark:border-mine-400">{{ $location->name }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($this->summaryLocations as $location)
                            <th class="min-w-32 border border-mine-200 p-2 text-center text-green-600 dark:border-mine-400">Bertambah</th>
                            @if ($location->type !== 'virtual')
                                <th class="min-w-32 border border-mine-200 p-2 text-center text-red-600 dark:border-mine-400">Berkurang</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->summaryProducts as $product)
                        <tr wire:key="summary-product-{{ $product->id }}">
                            <td class="sticky left-0 border border-mine-200 bg-white p-3 dark:border-mine-400 dark:bg-neutral-700">
                                <div class="font-semibold">{{ $product->name }}</div><div class="text-xs text-zinc-500">{{ $product->sku }}</div>
                            </td>
                            @foreach ($this->summaryLocations as $location)
                                @php($movementSummary = $this->summary[$product->id][$location->id] ?? ['increase' => 0, 'decrease' => 0])
                                <td class="border border-mine-200 p-3 text-right font-semibold text-green-600 dark:border-mine-400">{{ number_format($movementSummary['increase']) }} Pcs</td>
                                @if ($location->type !== 'virtual')
                                    <td class="border border-mine-200 p-3 text-right font-semibold text-red-600 dark:border-mine-400">{{ number_format($movementSummary['decrease']) }} Pcs</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="100%" class="border border-mine-200 p-8 text-center text-mine-300 dark:border-mine-400">Belum ada data untuk dirangkum.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:sidebar-content>
</div>
