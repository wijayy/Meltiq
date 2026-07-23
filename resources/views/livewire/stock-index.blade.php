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
        <div class="flex min-w-3xl flex-wrap items-end gap-4">
            <div class="w-full sm:w-64">
                <flux:input wire:model.live="selectedDateTime" type="datetime-local" label="Waktu Stok" />
            </div>
            <div class="w-full sm:w-64">
                <flux:select wire:model.live="productSlug" label="Produk">
                    <flux:select.option value="">Semua Produk</flux:select.option>
                    @foreach ($this->products as $product)
                        <flux:select.option wire:key="stock-filter-product-{{ $product->id }}" value="{{ $product->slug }}">
                            {{ $product->name }} — {{ $product->sku }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-full sm:w-64">
                <flux:select wire:model.live="locationSlug" label="Lokasi">
                    <flux:select.option value="">Semua Lokasi</flux:select.option>
                    @foreach ($this->locations as $location)
                        <flux:select.option wire:key="stock-filter-location-{{ $location->id }}" value="{{ $location->slug }}">
                            {{ $location->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if ($selectedDateTime !== '')
                <flux:button wire:click="resetDateTime" icon="arrow-path" variant="ghost">Stok Saat Ini</flux:button>
            @endif
        </div>

        <flux:callout icon="information-circle" color="zinc">
            Fisik adalah stok pada waktu laporan. Terjual, dikembalikan, dan kedaluwarsa merupakan akumulasi
            pergerakan keluar dari lokasi sampai waktu tersebut.
        </flux:callout>

        <div class="overflow-x-auto">
            <table class="min-w-6xl w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th rowspan="2" class="w-12 border border-mine-200 p-3 dark:border-mine-400">#</th>
                        <th rowspan="2" class="min-w-52 border border-mine-200 p-3 text-left dark:border-mine-400">Lokasi</th>
                        <th rowspan="2" class="min-w-64 border border-mine-200 p-3 text-left dark:border-mine-400">Produk</th>
                        <th colspan="5" class="border border-mine-200 p-3 text-center dark:border-mine-400">Rangkuman Stok</th>
                    </tr>
                    <tr>
                        <th class="min-w-28 border border-mine-200 p-2 text-right text-red-600 dark:border-mine-400">Kedaluwarsa</th>
                        <th class="min-w-28 border border-mine-200 p-2 text-right text-amber-600 dark:border-mine-400">Dikembalikan</th>
                        <th class="min-w-28 border border-mine-200 p-2 text-right text-mine-400 dark:border-mine-400 dark:text-mine-100">Fisik</th>
                        <th class="min-w-28 border border-mine-200 p-2 text-right text-green-600 dark:border-mine-400">Terjual</th>
                        <th class="min-w-28 border border-mine-200 p-2 text-right dark:border-mine-400">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->stocks as $index => $stock)
                        <tr wire:key="stock-{{ $stock['product_id'] }}-{{ $stock['location_id'] }}">
                            <td class="border border-mine-200 p-3 text-center dark:border-mine-400">{{ $index + 1 }}</td>
                            <td class="border border-mine-200 p-3 dark:border-mine-400">
                                <div class="font-semibold">{{ $stock['location_name'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $this->locationTypeLabel($stock['location_type']) }}</div>
                            </td>
                            <td class="border border-mine-200 p-3 dark:border-mine-400">
                                <div class="font-semibold text-mine-300 dark:text-mine-100">{{ $stock['product_name'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $stock['sku'] }}</div>
                            </td>
                            <td class="border border-mine-200 p-3 text-right text-red-600 dark:border-mine-400">{{ number_format($stock['expired']) }} Pcs</td>
                            <td class="border border-mine-200 p-3 text-right text-amber-600 dark:border-mine-400">{{ number_format($stock['returned']) }} Pcs</td>
                            <td class="border border-mine-200 p-3 text-right font-semibold text-mine-400 dark:border-mine-400 dark:text-mine-100">{{ number_format($stock['physical']) }} Pcs</td>
                            <td class="border border-mine-200 p-3 text-right text-green-600 dark:border-mine-400">{{ number_format($stock['sales']) }} Pcs</td>
                            <td class="border border-mine-200 p-3 text-right font-bold dark:border-mine-400">{{ number_format($stock['total']) }} Pcs</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-mine-200 p-10 text-center text-mine-300 dark:border-mine-400 dark:text-mine-100">
                                Data stok tidak ditemukan pada filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:sidebar-content>
</div>
