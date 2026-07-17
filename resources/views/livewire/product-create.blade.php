<div>
    <flux:modal name="product-create" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg" class="text-mine-400 dark:text-mine-100">{{ $title }}</flux:heading>
                <flux:text class="mt-1 text-mine-300 dark:text-mine-200">
                    Lengkapi informasi produk untuk kebutuhan produksi dan konsinyasi.
                </flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="categoryId" label="Category" placeholder="Pilih category" required>
                    @foreach ($this->categories as $category)
                        <flux:select.option wire:key="product-category-{{ $category->id }}" value="{{ $category->id }}">
                            {{ $category->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="sku" label="SKU" placeholder="Contoh: SKU-001" required />

                <div class="md:col-span-2">
                    <flux:input wire:model="name" label="Nama Product" placeholder="Masukkan nama product" required />
                </div>

                <flux:input wire:model="costPrice" type="number" min="0" label="Harga Modal" required />
                <flux:input wire:model="transferPrice" type="number" min="0" label="Harga Transfer" required />
                <flux:input wire:model="salePrice" type="number" min="0" label="Harga Jual" required />

                <div class="flex items-end pb-2">
                    <flux:switch wire:model="isActive" label="Product Aktif" />
                </div>

                <div class="md:col-span-2">
                    <flux:textarea wire:model="description" label="Deskripsi" placeholder="Masukkan deskripsi product"
                        rows="3" required />
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-mine-200 pt-4 dark:border-mine-400">
                <flux:button type="button" wire:click="closeModal" variant="ghost">Batal</flux:button>
                <flux:button type="submit" icon="check" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
