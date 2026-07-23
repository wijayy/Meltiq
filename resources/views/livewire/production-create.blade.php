<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}

        <x-slot name="button">
            <flux:button :href="route('productions.index')" wire:navigate icon="arrow-left" variant="ghost" size="sm">
                Kembali
            </flux:button>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="productionDate" type="date" label="Tanggal Produksi" required />
                <flux:input :value="$this->warehouse?->name ?? 'Belum dikonfigurasi'" label="Gudang Tujuan" readonly />
            </div>
            @unless ($this->warehouse)
                <flux:callout color="red" icon="exclamation-triangle">
                    Default warehouse belum dikonfigurasi atau tidak aktif. Atur warehouse pada Pengaturan Sistem sebelum menyimpan produksi.
                </flux:callout>
            @endunless
            <flux:textarea wire:model="notes" label="Catatan" rows="2" placeholder="Catatan produksi (opsional)" />

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="text-mine-400 dark:text-mine-100">Detail Produk</flux:heading>
                    <flux:button type="button" wire:click="addDetail" icon="plus" variant="primary" size="sm">
                        Tambah Produk
                    </flux:button>
                </div>

                <div class="flex min-w-3xl items-center gap-4 text-sm font-semibold">
                    <div class="w-10">#</div>
                    <div class="w-3/5">Produk</div>
                    <div class="w-1/5 text-center">Jumlah</div>
                    <div class="w-1/5 text-center">Aksi</div>
                </div>

                @foreach ($details as $index => $detail)
                    <div wire:key="production-detail-{{ $index }}"
                        class="flex min-w-3xl items-start gap-4 border-t border-mine-200 pt-3 dark:border-mine-400">
                        <div class="w-10 pt-2">{{ $index + 1 }}</div>
                        <div class="w-3/5">
                            <flux:select wire:model="details.{{ $index }}.product_id" placeholder="Pilih product">
                                @foreach ($this->products as $product)
                                    <flux:select.option wire:key="production-product-{{ $index }}-{{ $product->id }}"
                                        value="{{ $product->id }}">
                                        {{ $product->name }} — {{ $product->sku }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="details.{{ $index }}.product_id" />
                        </div>
                        <div class="w-1/5">
                            <flux:input wire:model="details.{{ $index }}.qty" type="number" min="1" />
                            <flux:error name="details.{{ $index }}.qty" />
                        </div>
                        <div class="flex w-1/5 justify-center">
                            <flux:button type="button" wire:click="removeDetail({{ $index }})" icon="trash"
                                variant="danger" size="sm" :disabled="count($details) === 1">
                                Hapus
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2 border-t border-mine-200 pt-4 dark:border-mine-400">
                <flux:button :href="route('productions.index')" wire:navigate variant="ghost">Batal</flux:button>
                <flux:button type="submit" icon="check" variant="primary" :disabled="! $this->warehouse">
                    {{ $production ? 'Simpan Perubahan' : 'Simpan Produksi' }}
                </flux:button>
            </div>
        </form>
    </flux:sidebar-content>
</div>
