<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}

        <x-slot name="button">
            <div class="flex gap-2">
                <flux:button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    icon="arrow-down-tray" variant="ghost" size="sm">
                    <span wire:loading.remove wire:target="exportExcel">Ekspor Excel</span>
                    <span wire:loading wire:target="exportExcel">Membuat Excel...</span>
                </flux:button>
                <flux:button :href="route('productions.create')" wire:navigate icon="plus" variant="primary" size="sm">
                    Tambah Produksi
                </flux:button>
            </div>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <div class="flex min-w-4xl flex-wrap items-end gap-4">
            <div class="w-full sm:w-64">
                <flux:input wire:model.live.debounce.300ms="productionNo" icon="magnifying-glass"
                    label="Nomor Produksi" placeholder="Cari nomor produksi..." />
            </div>
            <div class="w-full sm:w-48">
                <flux:input wire:model.live="periodBegin" type="date" label="Awal Periode" />
            </div>
            <div class="w-full sm:w-48">
                <flux:input wire:model.live="periodEnd" type="date" label="Akhir Periode" />
            </div>
            <div class="w-full sm:w-64">
                <flux:input wire:model.live.debounce.300ms="createdBy" icon="user"
                    label="Dibuat Oleh" placeholder="Cari nama pembuat..." />
            </div>
        </div>

        <div class="flex min-w-4xl items-center gap-4 text-sm font-semibold">
            <div class="w-10">#</div>
            <div class="w-1/4">Nomor Produksi</div>
            <div class="w-1/5 text-center">Tanggal</div>
            <div class="w-1/6 text-center">Produk</div>
            <div class="w-1/6 text-center">Total Qty</div>
            <div class="w-1/4">Dibuat Oleh</div>
            <div class="w-40 text-center">Aksi</div>
        </div>

        @forelse ($this->productions as $index => $production)
            <div wire:key="production-{{ $production->id }}"
                class="flex min-w-4xl items-center gap-4 border-t border-mine-200 pt-2 text-sm dark:border-mine-400">
                <div class="w-10">{{ $index + 1 }}</div>
                <div class="w-1/4 font-semibold text-mine-300 dark:text-mine-100">{{ $production->production_no }}</div>
                <div class="w-1/5 text-center">{{ $production->production_date->format('d/m/Y') }}</div>
                <div class="w-1/6 text-center">{{ $production->details_count }}</div>
                <div class="w-1/6 text-center">{{ number_format($production->details_sum_qty ?? 0) }} Pcs</div>
                <div class="w-1/4">{{ $production->creator->name }}</div>
                <div class="flex w-40 justify-center gap-1">
                    <flux:button :href="route('productions.show', $production)" wire:navigate icon="eye"
                        variant="ghost" size="sm">Lihat</flux:button>
                    @if ($production->isEditable())
                        <flux:button :href="route('productions.edit', $production)" wire:navigate icon="pencil-square"
                            variant="ghost" size="sm">Ubah</flux:button>
                    @else
                        <flux:badge color="zinc" size="sm">Terkunci</flux:badge>
                    @endif
                </div>
            </div>
        @empty
            <div class="min-w-4xl border-t border-mine-200 py-10 text-center text-sm text-mine-300 dark:border-mine-400 dark:text-mine-100">
                Data produksi belum tersedia.
            </div>
        @endforelse
    </flux:sidebar-content>
</div>
