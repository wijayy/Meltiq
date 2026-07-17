<div class="space-y-4">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}

        <x-slot name="button">
            <flux:button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                icon="arrow-down-tray" variant="primary" size="sm">
                <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                <span wire:loading wire:target="exportExcel">Membuat Excel...</span>
            </flux:button>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <div class="flex min-w-3xl flex-wrap items-end gap-4">
            <div class="w-full sm:w-64">
                <flux:input wire:model.live="selectedDateTime" type="datetime-local" label="Waktu Stock" />
            </div>
            <div class="w-full sm:w-64">
                <flux:select wire:model.live="productSlug" label="Product">
                    <flux:select.option value="">Semua Product</flux:select.option>
                    @foreach ($this->products as $product)
                        <flux:select.option wire:key="stock-filter-product-{{ $product->id }}" value="{{ $product->slug }}">
                            {{ $product->name }} — {{ $product->sku }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-full sm:w-64">
                <flux:select wire:model.live="locationSlug" label="Location">
                    <flux:select.option value="">Semua Location</flux:select.option>
                    @foreach ($this->locations as $location)
                        <flux:select.option wire:key="stock-filter-location-{{ $location->id }}" value="{{ $location->slug }}">
                            {{ $location->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if ($selectedDateTime !== '')
                <flux:button wire:click="resetDateTime" icon="arrow-path" variant="ghost">Stock Saat Ini</flux:button>
            @endif
        </div>

        <div class="flex min-w-3xl items-center gap-4 text-sm font-semibold">
            <div class="w-10">#</div>
            <div class="w-2/5">Product</div>
            <div class="w-2/5">Location</div>
            <div class="w-1/5 text-right">Stock</div>
        </div>

        @forelse ($this->stocks as $index => $stock)
            <div wire:key="stock-{{ $stock['product_id'] }}-{{ $stock['location_id'] }}"
                class="flex min-w-3xl items-center gap-4 border-t border-mine-200 pt-2 text-sm dark:border-mine-400">
                <div class="w-10">{{ $index + 1 }}</div>
                <div class="w-2/5">
                    <div class="font-semibold text-mine-300 dark:text-mine-100">{{ $stock['product_name'] }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $stock['sku'] }}</div>
                </div>
                <div class="w-2/5">
                    <div class="font-semibold">{{ $stock['location_name'] }}</div>
                    <flux:badge color="zinc" size="sm">{{ ucfirst($stock['location_type']) }}</flux:badge>
                </div>
                <div class="w-1/5 text-right text-base font-semibold text-mine-400 dark:text-mine-100">
                    {{ number_format($stock['stock']) }} Pcs
                </div>
            </div>
        @empty
            <div class="min-w-3xl border-t border-mine-200 py-10 text-center text-sm text-mine-300 dark:border-mine-400 dark:text-mine-100">
                Data stock tidak ditemukan pada filter yang dipilih.
            </div>
        @endforelse
    </flux:sidebar-content>
</div>
