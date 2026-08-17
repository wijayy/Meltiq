{{-- BEGIN KODE INTI SKRIPSI: TEMPLATE BLADE LIVEWIRE --}}
<div class="flex h-[calc(100dvh-8rem)] flex-col gap-4 overflow-hidden lg:h-[calc(100dvh-4rem)]">
    <x-slot name="title">{{ $title }}</x-slot>

    <flux:sidebar-header>
        {{ $title }}
        <x-slot name="button">
            <flux:modal.trigger name="export-financial-report">
                <flux:button icon="arrow-down-tray" variant="primary" size="sm">Ekspor Excel</flux:button>
            </flux:modal.trigger>
        </x-slot>
    </flux:sidebar-header>

    <flux:sidebar-content>
        <section
            class="grid gap-3 rounded-xl border border-mine-200 bg-white p-4 dark:border-mine-400 dark:bg-neutral-700 md:grid-cols-4">
            <flux:input type="date" wire:model.live="dateFrom" label="Dari tanggal" />
            <flux:input type="date" wire:model.live="dateTo" label="Sampai tanggal" />
            <flux:select wire:model.live="productSlug" label="Produk">
                <flux:select.option value="">Semua produk</flux:select.option>
                @foreach ($this->products as $product)
                    <flux:select.option :value="$product->slug">{{ $product->name }} — {{ $product->sku }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="locationSlug" label="Lokasi">
                <flux:select.option value="">Semua lokasi</flux:select.option>
                @foreach ($this->locations as $location)
                    <flux:select.option :value="$location->slug">{{ $location->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </section>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['label' => 'Omzet', 'value' => $this->report['summary']['revenue']], ['label' => 'Nilai Terjual', 'value' => $this->report['summary']['cost_of_goods_sold']], ['label' => 'Laba Kotor', 'value' => $this->report['summary']['gross_profit']], ['label' => 'Nilai Produksi', 'value' => $this->report['summary']['production_value']], ['label' => 'Nilai Pengiriman', 'value' => $this->report['summary']['transfer_value']], ['label' => 'Kerugian Produk Rusak', 'value' => $this->report['summary']['loss_value']], ['label' => 'Kontribusi Bersih', 'value' => $this->report['summary']['net_contribution']], ['label' => 'Nilai Persediaan', 'value' => $this->report['summary']['inventory_cost_value']]] as $metric)
                <div class="rounded-xl border border-mine-200 bg-white p-4 dark:border-mine-400 dark:bg-neutral-700">
                    <div class="text-xs text-zinc-500">{{ $metric['label'] }}</div>
                    <div class="mt-2 text-lg font-bold text-mine-400 dark:text-mine-100">Rp
                        {{ number_format($metric['value'], 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <section
                class="overflow-hidden rounded-xl border border-mine-200 bg-white dark:border-mine-400 dark:bg-neutral-700">
                <div class="p-4">
                    <flux:heading>Produksi</flux:heading>
                    <flux:text>Nilai hasil produksi berdasarkan cost price saat transaksi.</flux:text>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-mine-100/70 text-left dark:bg-neutral-800">
                            <tr>
                                <th class="p-3">Produk</th>
                                <th class="p-3 text-right">Diproduksi</th>
                                <th class="p-3 text-right">Nilai Produksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->report['productions'] as $row)
                                <tr class="border-t border-mine-100 dark:border-mine-400">
                                    <td class="p-3">
                                        <div class="font-semibold">{{ $row['product'] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $row['sku'] }}</div>
                                    </td>
                                    <td class="p-3 text-right">{{ number_format($row['quantity']) }} Pcs</td>
                                    <td class="p-3 text-right font-semibold">Rp
                                        {{ number_format($row['cost_value'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-8 text-center text-zinc-500">Tidak ada produksi pada
                                        periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-mine-200 bg-white dark:border-mine-400 dark:bg-neutral-700">
                <div class="p-4">
                    <flux:heading>Pengiriman ke Outlet</flux:heading>
                    <flux:text>Transfer internal, bukan omzet perusahaan.</flux:text>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-mine-100/70 text-left dark:bg-neutral-800">
                            <tr>
                                <th class="p-3">Produk</th>
                                <th class="p-3">Tujuan</th>
                                <th class="p-3 text-right">Dikirim</th>
                                <th class="p-3 text-right">Nilai Transfer</th>
                                <th class="p-3 text-right">Margin Internal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->report['transfers'] as $row)
                                <tr class="border-t border-mine-100 dark:border-mine-400">
                                    <td class="p-3">
                                        <div class="font-semibold">{{ $row['product'] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $row['sku'] }}</div>
                                    </td>
                                    <td class="p-3">{{ $row['destination'] }}</td>
                                    <td class="p-3 text-right">{{ number_format($row['quantity']) }} Pcs</td>
                                    <td class="p-3 text-right font-semibold">Rp
                                        {{ number_format($row['transfer_value'], 0, ',', '.') }}</td>
                                    <td class="p-3 text-right">Rp
                                        {{ number_format($row['internal_margin'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-zinc-500">Tidak ada pengiriman pada
                                        periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <section
                class="overflow-hidden rounded-xl border border-mine-200 bg-white dark:border-mine-400 dark:bg-neutral-700 xl:col-span-2">
                <div class="flex items-center justify-between p-4">
                    <div>
                        <flux:heading>Penjualan & Laba Kotor</flux:heading>
                        <flux:text>Margin kotor
                            {{ number_format($this->report['summary']['gross_margin'], 1, ',', '.') }}%</flux:text>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-mine-100/70 text-left dark:bg-neutral-800">
                            <tr>
                                <th class="p-3">Produk</th>
                                <th class="p-3 text-right">Terjual</th>
                                <th class="p-3 text-right">Omzet</th>
                                <th class="p-3 text-right">Nilai Terjual</th>
                                <th class="p-3 text-right">Laba Kotor</th>
                                <th class="p-3 text-right">Margin Outlet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->report['sales'] as $row)
                                <tr class="border-t border-mine-100 dark:border-mine-400">
                                    <td class="p-3">
                                        <div class="font-semibold">{{ $row['product'] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $row['sku'] }}</div>
                                    </td>
                                    <td class="p-3 text-right">{{ number_format($row['quantity']) }}</td>
                                    <td class="p-3 text-right">Rp {{ number_format($row['revenue'], 0, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-right">Rp
                                        {{ number_format($row['cost_of_goods_sold'], 0, ',', '.') }}</td>
                                    <td class="p-3 text-right font-semibold">Rp
                                        {{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
                                    <td class="p-3 text-right">Rp
                                        {{ number_format($row['outlet_margin'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-zinc-500">Tidak ada penjualan pada
                                        periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-mine-200 bg-white dark:border-mine-400 dark:bg-neutral-700">
                <div class="p-4">
                    <flux:heading>Kerugian Produk Rusak</flux:heading>
                    <flux:text>Dinilai menggunakan cost price saat transaksi.</flux:text>
                </div>
                <div class="divide-y divide-mine-100 dark:divide-mine-400">
                    @forelse ($this->report['losses'] as $row)
                        <div class="flex items-center justify-between p-4 text-sm">
                            <div>
                                <div class="font-semibold">{{ $row['product'] }}</div>
                                <div class="text-xs text-zinc-500">{{ number_format($row['quantity']) }} Pcs</div>
                            </div>
                            <div class="font-semibold text-red-600">Rp {{ number_format($row['value'], 0, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-zinc-500">Tidak ada barang kedaluwarsa.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <section
            class="overflow-hidden rounded-xl border border-mine-200 bg-white dark:border-mine-400 dark:bg-neutral-700">
            <div class="p-4">
                <flux:heading>Nilai Persediaan Saat Ini</flux:heading>
                <flux:text>Nilai buku menggunakan cost price master saat ini.</flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-mine-100/70 text-left dark:bg-neutral-800">
                        <tr>
                            <th class="p-3">Produk</th>
                            <th class="p-3">Lokasi</th>
                            <th class="p-3 text-right">Stok</th>
                            <th class="p-3 text-right">Nilai Cost</th>
                            <th class="p-3 text-right">Nilai Transfer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->report['inventory'] as $row)
                            <tr class="border-t border-mine-100 dark:border-mine-400">
                                <td class="p-3">
                                    <div class="font-semibold">{{ $row['product'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $row['sku'] }}</div>
                                </td>
                                <td class="p-3">{{ $row['location'] }}</td>
                                <td class="p-3 text-right">{{ number_format($row['quantity']) }} Pcs</td>
                                <td class="p-3 text-right">Rp {{ number_format($row['cost_value'], 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-right">Rp {{ number_format($row['transfer_value'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-zinc-500">Tidak ada persediaan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-xl border border-mine-200 bg-white dark:border-mine-400 dark:bg-neutral-700">
            <div class="p-4">
                <flux:heading>Rekonsiliasi Stok</flux:heading>
                <flux:text>Stok awal + masuk − keluar harus sama dengan stok akhir.</flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-mine-100/70 text-left dark:bg-neutral-800">
                        <tr>
                            <th class="p-3">Produk</th>
                            <th class="p-3">Lokasi</th>
                            <th class="p-3 text-right">Awal</th>
                            <th class="p-3 text-right">Masuk</th>
                            <th class="p-3 text-right">Keluar</th>
                            <th class="p-3 text-right">Seharusnya</th>
                            <th class="p-3 text-right">Akhir</th>
                            <th class="p-3 text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->report['reconciliation'] as $row)
                            <tr class="border-t border-mine-100 dark:border-mine-400">
                                <td class="p-3">
                                    <div class="font-semibold">{{ $row['product'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $row['sku'] }}</div>
                                </td>
                                <td class="p-3">{{ $row['location'] }}</td>
                                <td class="p-3 text-right">{{ number_format($row['opening']) }}</td>
                                <td class="p-3 text-right">{{ number_format($row['incoming']) }}</td>
                                <td class="p-3 text-right">{{ number_format($row['outgoing']) }}</td>
                                <td class="p-3 text-right">{{ number_format($row['expected_closing']) }}</td>
                                <td class="p-3 text-right">{{ number_format($row['closing']) }}</td>
                                <td class="p-3 text-right">
                                    <flux:badge :color="$row['variance'] === 0 ? 'green' : 'red'">
                                        {{ number_format($row['variance']) }}</flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-zinc-500">Tidak ada pergerakan stok
                                    pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <flux:callout icon="information-circle" color="blue">
            Laporan ini adalah laporan manajemen berbasis stok. Kontribusi bersih = omzet − nilai terjual − kerugian produk rusak;
            belum memperhitungkan biaya operasional, kas, pajak, utang, atau piutang.
        </flux:callout>
    </flux:sidebar-content>

    <flux:modal name="export-financial-report" :show="$errors->has('exportSections')" class="max-w-lg">
        <form wire:submit="exportExcel" class="space-y-6">
            <div>
                <flux:heading size="lg">Pilih Data yang Diekspor</flux:heading>
                <flux:subheading>Setiap bagian akan dibuat sebagai sheet terpisah dalam satu file Excel.
                </flux:subheading>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
        'summary' => 'Ringkasan',
        'sales' => 'Penjualan & Laba',
        'productions' => 'Produksi',
        'transfers' => 'Pengiriman',
        'losses' => 'Kerugian Produk Rusak',
        'inventory' => 'Nilai Persediaan',
        'reconciliation' => 'Rekonsiliasi Stok',
    ] as $value => $label)
                    <flux:checkbox wire:model="exportSections" :value="$value" :label="$label" />
                @endforeach
            </div>
            @error('exportSections')
                <flux:text class="text-red-600">{{ $message }}</flux:text>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="arrow-down-tray" wire:loading.attr="disabled"
                    wire:target="exportExcel">
                    <span wire:loading.remove wire:target="exportExcel">Unduh Excel</span>
                    <span wire:loading wire:target="exportExcel">Membuat Excel...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
{{-- END KODE INTI SKRIPSI: TEMPLATE BLADE LIVEWIRE --}}
