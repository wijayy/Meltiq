<?php

namespace App\Livewire;

use App\Actions\SaveVisit;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

class VisitCreate extends Component
{
    public string $title = 'Tambah Pengiriman';

    public ?Visit $visit = null;

    public string $visitDate = '';

    public string $locationId = '';

    public string $notes = '';

    /** @var array<int, array{product_id: string|int, warehouseStock: int, stockBefore: int, physicalStock: string|int, returnedQty: string|int, expiredQty: string|int, newDeliveryQty: string|int, isOutletStock: bool}> */
    public array $details = [];

    public function mount(?Visit $visit = null): void
    {
        if ($visit?->exists) {
            abort_unless($visit->isEditable(), 403, 'Pengiriman sudah masuk rekaman stok dan tidak dapat diubah.');
            $this->visit = $visit;
            $this->title = 'Ubah Pengiriman';
            $this->visitDate = $visit->visit_date->toDateString();
            $this->locationId = (string) $visit->location_id;
            $this->notes = $visit->notes ?? '';
            $this->details = $visit->details()->get()->map(fn (VisitDetail $detail): array => [
                'product_id' => $detail->product_id,
                'warehouseStock' => $this->warehouseStockFor($detail->product_id),
                'stockBefore' => $detail->stockBefore,
                'physicalStock' => $detail->physicalStock,
                'returnedQty' => $detail->returnedQty,
                'expiredQty' => $detail->expiredQty,
                'newDeliveryQty' => $detail->newDeliveryQty,
                'isOutletStock' => $detail->stockBefore > 0,
            ])->all();

            return;
        }

        $this->visitDate = now()->toDateString();
    }

    /** @return Collection<int, Location> */
    #[Computed]
    public function outlets(): Collection
    {
        return Location::query()->active()->where('type', 'outlet')->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']);
    }

    public function addDetail(): void
    {
        if ($this->locationId === '') {
            return;
        }

        $this->details[] = ['product_id' => '', 'warehouseStock' => 0, 'stockBefore' => 0, 'physicalStock' => 0, 'returnedQty' => 0, 'expiredQty' => 0, 'newDeliveryQty' => 0, 'isOutletStock' => false];
    }

    public function removeDetail(int $index): void
    {
        if (($this->details[$index]['isOutletStock'] ?? true) === true) {
            return;
        }
        unset($this->details[$index]);
        $this->details = array_values($this->details);
    }

    public function updatedLocationId(): void
    {
        $this->loadOutletProducts();
    }

    public function updatedDetails(mixed $value, ?string $key): void
    {
        if (str_ends_with($key, '.product_id')) {
            $index = (int) explode('.', $key)[0];
            $detail = $this->details[$index] ?? null;

            if ($detail && $detail['isOutletStock'] === false) {
                $productId = (int) $detail['product_id'];
                $duplicate = collect($this->details)
                    ->except([$index])
                    ->contains(fn (array $row): bool => (int) $row['product_id'] === $productId);

                if ($productId > 0 && $duplicate) {
                    $this->details[$index] = [...$detail, 'product_id' => '', 'warehouseStock' => 0];
                    $this->addError("details.$index.product_id", 'Produk sudah ada di formulir kunjungan.');

                    return;
                }

                $this->details[$index] = [
                    ...$detail,
                    'warehouseStock' => $productId > 0 ? $this->warehouseStockFor($productId) : 0,
                    'stockBefore' => 0,
                    'physicalStock' => 0,
                    'returnedQty' => 0,
                    'expiredQty' => 0,
                ];
            }
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'visitDate' => ['required', 'date', 'before_or_equal:today'],
            'locationId' => ['required', 'integer', Rule::exists('locations', 'id')->where(fn ($query) => $query->where('type', 'outlet')->where('isActive', true))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('isActive', true)],
            'details.*.stockBefore' => ['required', 'integer', 'min:0'],
            'details.*.physicalStock' => ['required', 'integer', 'min:0'],
            'details.*.returnedQty' => ['required', 'integer', 'min:0'],
            'details.*.expiredQty' => ['required', 'integer', 'min:0'],
            'details.*.newDeliveryQty' => ['required', 'integer', 'min:0'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $detailPayloads = array_map(fn (array $detail): array => [
            'product_id' => (int) $detail['product_id'],
            'stockBefore' => (int) $detail['stockBefore'],
            'physicalStock' => (int) $detail['physicalStock'],
            'returnedQty' => (int) $detail['returnedQty'],
            'expiredQty' => (int) $detail['expiredQty'],
            'newDeliveryQty' => (int) $detail['newDeliveryQty'],
        ], $validated['details']);

        app(SaveVisit::class)->handle(
            creator: $user,
            outlet: Location::query()->findOrFail((int) $validated['locationId']),
            warehouse: $this->configuredLocation('default_warehouse_location', 'warehouse'),
            expiredLocation: $this->configuredLocation('default_expired_location', 'virtual'),
            visitDate: $validated['visitDate'],
            notes: $validated['notes'] ?: null,
            details: $detailPayloads,
            visit: $this->visit,
        );

        session()->flash('success', $this->visit ? 'Pengiriman berhasil diubah.' : 'Pengiriman berhasil disimpan.');
        $this->redirectRoute('visits.index', navigate: true);
    }

    private function loadOutletProducts(): void
    {
        $locationId = (int) $this->locationId;

        if ($locationId === 0) {
            $this->details = [];

            return;
        }

        $warehouseId = $this->configuredLocation('default_warehouse_location', 'warehouse')->id;
        $warehouseStocks = CurrentStock::query()
            ->where('location_id', $warehouseId)
            ->pluck('stock', 'product_id');

        $this->details = CurrentStock::query()
            ->where('location_id', $locationId)
            ->where('stock', '>', 0)
            ->orderBy('product_id')
            ->get(['product_id', 'stock'])
            ->map(fn (CurrentStock $stock): array => [
                'product_id' => $stock->product_id,
                'warehouseStock' => (int) ($warehouseStocks[$stock->product_id] ?? 0),
                'stockBefore' => $stock->stock,
                'physicalStock' => $stock->stock,
                'returnedQty' => 0,
                'expiredQty' => 0,
                'newDeliveryQty' => 0,
                'isOutletStock' => true,
            ])->all();
    }

    private function warehouseStockFor(int $productId): int
    {
        $warehouseId = $this->configuredLocation('default_warehouse_location', 'warehouse')->id;

        return (int) CurrentStock::query()
            ->where('product_id', $productId)
            ->where('location_id', $warehouseId)
            ->value('stock');
    }

    private function configuredLocation(string $key, string $type): Location
    {
        $id = Setting::query()->where('key', $key)->value('value');
        $location = Location::query()->active()->where('type', $type)->find((int) $id);

        return $location ?? throw new RuntimeException('Konfigurasi location sistem belum lengkap.');
    }

    public function render(): View
    {
        return view('livewire.visit-create');
    }
}
