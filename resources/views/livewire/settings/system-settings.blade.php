<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="System" subheading="Atur location utama yang digunakan oleh sistem inventory.">
        <form wire:submit="save" class="space-y-6">
            <flux:callout icon="information-circle" color="zinc">
                Lokasi yang dipilih sebagai Gudang Utama dan Lokasi Kedaluwarsa tidak dapat dinonaktifkan atau diubah tipenya.
            </flux:callout>

            <flux:select wire:model="defaultWarehouseId" label="Gudang Utama" required>
                <flux:select.option value="">Pilih warehouse</flux:select.option>
                @foreach ($this->warehouses as $warehouse)
                    <flux:select.option wire:key="setting-warehouse-{{ $warehouse->id }}" value="{{ $warehouse->id }}">
                        {{ $warehouse->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="expiredLocationId" label="Lokasi Kedaluwarsa" required>
                <flux:select.option value="">Pilih virtual location</flux:select.option>
                @foreach ($this->expiredLocations as $location)
                    <flux:select.option wire:key="setting-expired-{{ $location->id }}" value="{{ $location->id }}">
                        {{ $location->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end">
                <flux:button type="submit" icon="check" variant="primary">Simpan Konfigurasi</flux:button>
            </div>
        </form>
    </x-settings.layout>
</section>
