<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
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
                <flux:input wire:model="visitDate" type="date" label="Tanggal Pengiriman" required />
                <flux:select wire:model.live="locationId" label="Outlet" required>
                    <flux:select.option value="">Pilih outlet</flux:select.option>
                    @foreach ($this->outlets as $outlet)
                        <flux:select.option wire:key="visit-outlet-{{ $outlet->id }}" value="{{ $outlet->id }}">
                            {{ $outlet->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:textarea wire:model="notes" label="Catatan" rows="2" placeholder="Catatan pengiriman (opsional)" />
            <flux:callout icon="information-circle" color="zinc">
                <div class="space-y-1 text-sm">
                    <div><strong>Produk outlet:</strong> otomatis dimuat dan wajib diisi. Fisik adalah stok layak
                        jual yang ditemukan; kedaluwarsa dicatat terpisah.</div>
                    <div><strong>Perhitungan:</strong> Terjual = Stok Sebelum − Fisik − Kedaluwarsa. Stok akhir =
                        Fisik − Dikembalikan + Pengiriman Baru.</div>
                    <div><strong>Stok Gudang:</strong> hanya informasi stok yang tersedia di gudang untuk membantu
                        menentukan delivery.</div>
                    <div><strong>Produk baru:</strong> gunakan Tambah Produk dan isi Pengiriman Baru saja; kolom stok
                        lainnya dikunci karena produk belum pernah tersedia di outlet. Setiap produk hanya boleh muncul
                        satu kali.</div>
                </div>
            </flux:callout>
            <div class="flex w-full min-w-7xl items-center gap-3 text-sm font-semibold">
                <div class="w-8">#</div>
                <div class="min-w-64 flex-1">Produk</div>
                <div class="w-36 text-center">Stok Gudang</div>
                <div class="w-28 text-center">Stok Sebelum</div>
                <div class="w-28 text-center">Fisik</div>
                <div class="w-28 text-center">Dikembalikan</div>
                <div class="w-28 text-center">Kedaluwarsa</div>
                <div class="w-28 text-center">Pengiriman</div>
                <div class="w-20 text-center">Aksi</div>
            </div>
            @foreach ($details as $index => $detail)
                <div wire:key="visit-detail-{{ $index }}"
                    class="flex w-full min-w-7xl items-start gap-3 border-t border-mine-200 pt-3 dark:border-mine-400">
                    <div class="w-8 pt-2">{{ $index + 1 }}</div>
                    <div class="min-w-64 flex-1">
                        <flux:select wire:model.live="details.{{ $index }}.product_id"
                            placeholder="Pilih produk" :disabled="$detail['isOutletStock']">
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
                <div class="py-6 text-center text-sm text-zinc-500">Outlet belum memiliki stok. Tambahkan produk untuk
                    membuat pengiriman pertama.</div>
            @endif
            <div class="flex justify-between border-t border-mine-200 pt-4 dark:border-mine-400">
                <flux:button type="button" wire:click="addDetail" icon="plus" variant="ghost"
                    :disabled="$locationId === ''">Tambah Produk Baru</flux:button>
                <flux:button type="submit" icon="check" variant="primary">
                    {{ $visit ? 'Simpan Perubahan' : 'Simpan Pengiriman' }}</flux:button>
            </div>
        </form>
    </flux:sidebar-content>
</div>
