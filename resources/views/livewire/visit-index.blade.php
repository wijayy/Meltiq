<div class="space-y-4">
    <x-slot name="title">{{ $title }}</x-slot>
    <flux:sidebar-header>
        {{ $title }}
        <x-slot name="button">
            <div class="flex gap-2">
                <flux:button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    icon="arrow-down-tray" variant="ghost" size="sm">
                    <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                    <span wire:loading wire:target="exportExcel">Membuat Excel...</span>
                </flux:button>
                <flux:button :href="route('visits.create')" wire:navigate icon="plus" variant="primary" size="sm">Tambah Visit</flux:button>
            </div>
        </x-slot>
    </flux:sidebar-header>
    <flux:sidebar-content>
        <div class="flex min-w-4xl flex-wrap items-end gap-4">
            <div class="w-full sm:w-64"><flux:input wire:model.live.debounce.300ms="visitNo" label="Nomor Visit" /></div>
            <div class="w-full sm:w-64"><flux:input wire:model.live.debounce.300ms="location" label="Outlet" /></div>
            <div class="w-full sm:w-48"><flux:input wire:model.live="periodBegin" type="date" label="Period Begin" /></div>
            <div class="w-full sm:w-48"><flux:input wire:model.live="periodEnd" type="date" label="Period End" /></div>
        </div>
        <div class="flex min-w-4xl items-center gap-4 text-sm font-semibold">
            <div class="w-10">#</div><div class="w-1/4">Nomor Visit</div><div class="w-1/5">Tanggal</div><div class="w-1/4">Outlet</div><div class="w-1/6 text-center">Produk</div><div class="w-1/5">Created By</div><div class="w-40 text-center">Aksi</div>
        </div>
        @forelse ($this->visits as $index => $visit)
            <div wire:key="visit-{{ $visit->id }}" class="flex min-w-4xl items-center gap-4 border-t border-mine-200 pt-2 text-sm dark:border-mine-400">
                <div class="w-10">{{ $index + 1 }}</div><div class="w-1/4 font-semibold text-mine-300 dark:text-mine-100">{{ $visit->visit_no }}</div><div class="w-1/5">{{ $visit->visit_date->format('d/m/Y') }}</div><div class="w-1/4">{{ $visit->location->name }}</div><div class="w-1/6 text-center">{{ $visit->details_count }}</div><div class="w-1/5">{{ $visit->creator->name }}</div>
                <div class="flex w-40 justify-center gap-1">
                    <flux:button :href="route('visits.show', $visit)" wire:navigate icon="eye" variant="ghost" size="sm">Lihat</flux:button>
                    @if ($visit->isEditable())
                        <flux:button :href="route('visits.edit', $visit)" wire:navigate icon="pencil-square" variant="ghost" size="sm">Ubah</flux:button>
                    @else
                        <flux:badge color="zinc" size="sm">Terkunci</flux:badge>
                    @endif
                </div>
            </div>
        @empty
            <div class="min-w-4xl border-t border-mine-200 py-10 text-center text-sm text-mine-300 dark:border-mine-400">Data visit belum tersedia.</div>
        @endforelse
    </flux:sidebar-content>
</div>
