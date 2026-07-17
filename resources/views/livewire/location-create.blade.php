<div>
    <flux:modal name="location-create" class="max-w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg" class="text-mine-400 dark:text-mine-100">{{ $title }}</flux:heading>
                <flux:text class="mt-1 text-mine-300 dark:text-mine-200">
                    Warehouse menyimpan stok utama, outlet untuk konsinyasi, dan virtual untuk produk expired.
                </flux:text>
            </div>

            <flux:input wire:model="name" label="Nama Location" placeholder="Contoh: Outlet Renon" required />

            <flux:select wire:model="type" label="Tipe Location" required>
                <flux:select.option value="warehouse">Warehouse</flux:select.option>
                <flux:select.option value="outlet">Outlet</flux:select.option>
                <flux:select.option value="virtual">Virtual (Expired)</flux:select.option>
            </flux:select>

            <div class="flex justify-end gap-2 border-t border-mine-200 pt-4 dark:border-mine-400">
                <flux:button type="button" wire:click="closeModal" variant="ghost">Batal</flux:button>
                <flux:button type="submit" icon="check" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
