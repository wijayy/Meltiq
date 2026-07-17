<div class="space-y-4">
    <x-slot name="title">{{ $title }}</x-slot>
    <flux:sidebar-header>
        {{ $title }}
        <x-slot name="button">
            <flux:button :href="route('visits.index')" wire:navigate icon="arrow-left" variant="ghost" size="sm">
                Kembali</flux:button>
        </x-slot>
    </flux:sidebar-header>
    <flux:sidebar-content>
        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="visitDate" type="date" label="Tanggal Visit" required />
                <flux:select wire:model.live="locationId" label="Outlet" required>
                    <flux:select.option value="">Pilih outlet</flux:select.option>
                    @foreach ($this->outlets as $outlet)
                        <flux:select.option wire:key="visit-outlet-{{ $outlet->id }}" value="{{ $outlet->id }}">
                            {{ $outlet->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:textarea wire:model="notes" label="Catatan" rows="2" placeholder="Catatan visit (opsional)" />
            <flux:callout icon="information-circle" color="zinc">
                <div class="space-y-1 text-sm">
                    <div><strong>Produk outlet:</strong> otomatis dimuat dan wajib diisi. Physical adalah stok layak
                        jual yang ditemukan; expired dicatat terpisah.</div>
                    <div><strong>Perhitungan:</strong> Terjual = Stock Before − Physical − Expired. Stok akhir =
                        Physical − Returned + New Delivery.</div>
                    <div><strong>Stock Warehouse:</strong> hanya informasi stok yang tersedia di gudang untuk membantu
                        menentukan delivery.</div>
                    <div><strong>Produk baru:</strong> gunakan Tambah Product dan isi New Delivery saja; kolom stok
                        lainnya dikunci karena produk belum pernah tersedia di outlet. Setiap produk hanya boleh muncul
                        satu kali.</div>
                </div>
            </flux:callout>
            <div class="flex w-full min-w-7xl items-center gap-3 text-sm font-semibold">
                <div class="w-8">#</div>
                <div class="min-w-64 flex-1">Product</div>
                <div class="w-36 text-center">Stock Warehouse</div>
                <div class="w-28 text-center">Stock Before</div>
                <div class="w-28 text-center">Physical</div>
                <div class="w-28 text-center">Returned</div>
                <div class="w-28 text-center">Expired</div>
                <div class="w-28 text-center">Delivery</div>
                <div class="w-20 text-center">Aksi</div>
            </div>
            @foreach ($details as $index => $detail)
                <div wire:key="visit-detail-{{ $index }}"
                    class="flex w-full min-w-7xl items-start gap-3 border-t border-mine-200 pt-3 dark:border-mine-400">
                    <div class="w-8 pt-2">{{ $index + 1 }}</div>
                    <div class="min-w-64 flex-1">
                        <flux:select wire:model.live="details.{{ $index }}.product_id"
                            placeholder="Pilih product" :disabled="$detail['isOutletStock']">
                            @foreach ($this->products as $product)
                                <flux:select.option wire:key="visit-product-{{ $index }}-{{ $product->id }}"
                                    value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="details.{{ $index }}.product_id" />
                    </div>
                    <div class="w-36">
                        <flux:input wire:model="details.{{ $index }}.warehouseStock" type="number" readonly />
                    </div>
                    <div class="w-28">
                        <flux:input wire:model="details.{{ $index }}.stockBefore" type="number" readonly />
                    </div>
                    <div class="w-28">
                        <flux:input wire:model="details.{{ $index }}.physicalStock" type="number"
                            min="0" :readonly="!$detail['isOutletStock']" />
                    </div>
                    <div class="w-28">
                        <flux:input wire:model="details.{{ $index }}.returnedQty" type="number" min="0"
                            :readonly="!$detail['isOutletStock']" />
                    </div>
                    <div class="w-28">
                        <flux:input wire:model="details.{{ $index }}.expiredQty" type="number" min="0"
                            :readonly="!$detail['isOutletStock']" />
                    </div>
                    <div class="w-28">
                        <flux:input wire:model="details.{{ $index }}.newDeliveryQty" type="number"
                            min="0" />
                    </div>
                    <div class="flex w-20 justify-center">
                        @if ($detail['isOutletStock'])
                            <flux:tooltip content="Produk dengan stock outlet wajib diisi"><flux:icon.lock-closed
                                    class="mt-2 size-5 text-zinc-400" /></flux:tooltip>
                        @else
                            <flux:button type="button" wire:click="removeDetail({{ $index }})" icon="trash"
                                variant="danger" size="sm" />
                        @endif
                    </div>
                </div>
            @endforeach
            @if ($locationId !== '' && count($details) === 0)
                <div class="py-6 text-center text-sm text-zinc-500">Outlet belum memiliki stock. Tambahkan product untuk
                    membuat delivery pertama.</div>
            @endif
            <div class="flex justify-between border-t border-mine-200 pt-4 dark:border-mine-400">
                <flux:button type="button" wire:click="addDetail" icon="plus" variant="ghost"
                    :disabled="$locationId === ''">Tambah Product Baru</flux:button>
                <flux:button type="submit" icon="check" variant="primary">
                    {{ $visit ? 'Simpan Perubahan' : 'Simpan Visit' }}</flux:button>
            </div>
        </form>
    </flux:sidebar-content>
</div>
