<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}

        <x-slot name="button">
            <flux:button wire:click="createLocation" icon="plus" variant="primary" size="sm">
                Tambah Lokasi
            </flux:button>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <div class="flex w-full gap-4 sm:w-2/3 lg:w-1/2">
            <div class="w-2/3">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Cari nama atau tipe location..." />
            </div>
            <div class="w-1/3">
                <flux:select wire:model.live="status">
                    <flux:select.option value="active">Aktif</flux:select.option>
                    <flux:select.option value="nonactive">Tidak Aktif</flux:select.option>
                    <flux:select.option value="all">Semua</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="flex min-w-3xl items-center gap-4 text-sm font-semibold">
            <div class="w-10">#</div>
            <div class="w-1/2">Nama Lokasi</div>
            <div class="w-1/4 text-center">Tipe</div>
            <div class="w-1/4 text-center">Aksi</div>
        </div>

        @forelse ($this->locations as $index => $location)
            <div wire:key="location-{{ $location->id }}"
                class="flex min-w-3xl items-center gap-4 border-t border-mine-200 pt-2 text-sm dark:border-mine-400">
                <div class="w-10">{{ $index + 1 }}</div>
                <div class="w-1/2 font-semibold">{{ $location->name }}</div>
                <div class="w-1/4 text-center">
                    <flux:badge color="zinc" size="sm">{{ ucfirst($location->type) }}</flux:badge>
                </div>
                <div class="flex w-1/4 justify-center gap-2">
                    <flux:button wire:click="editLocation({{ $location->id }})" icon="pencil-square"
                        variant="primary" size="sm">
                        Ubah
                    </flux:button>
                    @if (! $location->isActive)
                        <flux:button wire:click="restoreLocation({{ $location->id }})" icon="arrow-path"
                            variant="primary" size="sm">
                            Pulihkan
                        </flux:button>
                    @elseif (! in_array($location->id, $this->systemLocationIds, true))
                        <flux:button wire:click="openDeleteModal({{ $location->id }})" icon="trash"
                            variant="danger" size="sm">
                            Hapus
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="min-w-3xl border-t border-mine-200 py-10 text-center text-sm text-mine-300 dark:border-mine-400 dark:text-mine-100">
                Lokasi tidak ditemukan.
            </div>
        @endforelse

        <flux:modal name="delete-location" class="max-w-md">
            <flux:heading size="lg">Nonaktifkan Outlet</flux:heading>
            <flux:text class="mt-2">
                Outlet tidak akan dihapus permanen, tetapi tidak lagi ditampilkan dan tidak dapat digunakan.
            </flux:text>

            <div class="mt-6 flex justify-end gap-2">
                <flux:button wire:click="closeDeleteModal" variant="ghost">Batal</flux:button>
                <flux:button wire:click="deleteLocation" icon="trash" variant="danger">Nonaktifkan</flux:button>
            </div>
        </flux:modal>
    </flux:sidebar-content>

    <livewire:location-create />
</div>
