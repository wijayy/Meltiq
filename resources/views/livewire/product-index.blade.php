<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">
        {{ $title }}
    </x-slot>

    <flux:sidebar-header>
        {{ $title }}

        <x-slot name="button">
            <flux:button wire:click="createCategory" variant="secondary" size="sm">
                Tambah Kategori
            </flux:button>
            <flux:button wire:click="createProduct" variant="primary" size="sm">
                Tambah Produk
            </flux:button>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content class="">
        <div class="flex w-full gap-4 sm:w-2/3 lg:w-1/2">
            <div class="w-2/3">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                    placeholder="Cari nama atau SKU product..." />
            </div>
            <div class="w-1/3">
                <flux:select wire:model.live="status">
                    <flux:select.option value="active">Aktif</flux:select.option>
                    <flux:select.option value="nonactive">Tidak Aktif</flux:select.option>
                    <flux:select.option value="all">Semua</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="flex items-center gap-4 min-w-3xl text-sm font-semibold">
            <div class="w-1/5">Name</div>
            <div class="w-1/5 text-center">SKU</div>
            <div class="w-1/5 text-center">Cost Price</div>
            <div class="w-1/5 text-center">Stok Tersedia</div>
            <div class="w-1/5 text-center">Actions</div>
        </div>
        @foreach ($this->categories as $key => $category)
            <div class="flex items-center gap-4 min-w-3xl border-t pt-2 border-mine-200 dark:border-mine-400">
                <div class="w-4/5 font-semibold">Kategori: {{ $category->name }}</div>
                <div class="w-1/5 flex justify-center gap-2">
                    @if ($category->isActive)
                        <flux:button wire:click="editCategory({{ $category->id }})" variant="primary" size="sm">
                            Ubah
                        </flux:button>
                        <flux:button wire:click="openDeleteModal('category', {{ $category->id }})" variant="danger"
                            size="sm">
                            Hapus
                        </flux:button>
                    @else
                        <flux:button wire:click="restoreCategory({{ $category->id }})" icon="arrow-path"
                            variant="primary" size="sm">
                            Restore
                        </flux:button>
                    @endif
                </div>
            </div>
            @foreach ($category->products as $index => $product)
                <div class="flex items-center gap-4 min-w-3xl">
                    <div class="w-1/5 ps-2">{{ $product->name }}</div>
                    <div class="w-1/5 text-center">{{ $product->sku }}</div>
                    <div class="w-1/5 text-center">Rp. {{ number_format($product->costPrice, 0) }}</div>
                    <div class="w-1/5 text-center">{{ $product->currentStockOnHand }} Pcs</div>
                    <div class="w-1/5 flex justify-center gap-2">
                        @if ($product->isActive)
                            <flux:button wire:click="editProduct({{ $product->id }})" variant="primary"
                                size="sm">
                                Ubah
                            </flux:button>
                            <flux:button wire:click="openDeleteModal('product', {{ $product->id }})" variant="danger"
                                size="sm">
                                Hapus
                            </flux:button>
                        @else
                            <flux:button wire:click="restoreProduct({{ $product->id }})" icon="arrow-path"
                                variant="primary" size="sm">
                                Restore
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </flux:sidebar-content>

    <flux:modal name="delete-product-data" class="max-w-md">
        <flux:heading size="lg">Nonaktifkan {{ $deleteType === 'category' ? 'Kategori' : 'Produk' }}
        </flux:heading>
        <flux:text class="mt-2">
            Data tidak akan dihapus permanen, tetapi tidak lagi ditampilkan. Jika category dinonaktifkan, seluruh
            product aktif di dalamnya juga akan dinonaktifkan.
        </flux:text>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="closeDeleteModal" variant="ghost">Batal</flux:button>
            <flux:button wire:click="deleteData" icon="trash" variant="danger">Nonaktifkan</flux:button>
        </div>
    </flux:modal>

    @livewire('category-create')
    @livewire('product-create')
</div>
