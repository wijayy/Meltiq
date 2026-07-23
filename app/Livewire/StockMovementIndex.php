<?php

namespace App\Livewire;

use App\Actions\BuildStockMovementExcel;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductionDetail;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\VisitDetail;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockMovementIndex extends Component
{
    public string $title = 'Pergerakan Stok';

    #[Url(as: 'period-begin', except: '')]
    public string $periodBegin = '';

    #[Url(as: 'period-end', except: '')]
    public string $periodEnd = '';

    #[Url(as: 'product', except: '')]
    public string $productSlug = '';

    #[Url(as: 'location', except: '')]
    public string $locationSlug = '';

    /** @return EloquentCollection<int, StockMovement> */
    #[Computed]
    public function movements(): EloquentCollection
    {
        return StockMovement::query()
            ->with([
                'product:id,name,sku,slug',
                'fromLocation:id,name,type,slug',
                'toLocation:id,name,type,slug',
                'reference' => function (Relation $relation): void {
                    if (! $relation instanceof MorphTo) {
                        return;
                    }

                    $relation->morphWith([
                        ProductionDetail::class => ['production:id,production_no,slug'],
                        VisitDetail::class => ['visit:id,visit_no,slug'],
                    ]);
                },
            ])
            ->when($this->periodBegin, fn ($query) => $query->whereDate('movement_date', '>=', $this->periodBegin))
            ->when($this->periodEnd, fn ($query) => $query->whereDate('movement_date', '<=', $this->periodEnd))
            ->when($this->productSlug, fn ($query) => $query->whereHas('product', fn ($query) => $query->where('slug', $this->productSlug)))
            ->when($this->locationSlug, fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('fromLocation', fn ($query) => $query->where('slug', $this->locationSlug))
                ->orWhereHas('toLocation', fn ($query) => $query->where('slug', $this->locationSlug))))
            ->latest('movement_date')
            ->latest('id')
            ->get();
    }

    /** @return EloquentCollection<int, Product> */
    #[Computed]
    public function products(): EloquentCollection
    {
        return Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'slug']);
    }

    /** @return EloquentCollection<int, Location> */
    #[Computed]
    public function locations(): EloquentCollection
    {
        return Location::query()
            ->orderByRaw("case type when 'warehouse' then 1 when 'outlet' then 2 else 3 end")
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'slug']);
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function summaryProducts(): Collection
    {
        return $this->movements()->pluck('product')->filter()->unique('id')->sortBy('name')->values();
    }

    /** @return Collection<int, Location> */
    #[Computed]
    public function summaryLocations(): Collection
    {
        $locations = $this->movements()
            ->flatMap(fn (StockMovement $movement): array => [$movement->fromLocation, $movement->toLocation])
            ->filter()
            ->unique('id');
        $expiredLocationId = (int) Setting::query()
            ->where('key', 'default_expired_location')
            ->value('value');

        if ($expiredLocationId > 0 && ! $locations->contains('id', $expiredLocationId)) {
            $expiredLocation = Location::query()->find($expiredLocationId);

            if ($expiredLocation !== null) {
                $locations->push($expiredLocation);
            }
        }

        return $locations
            ->sortBy('name')
            ->values();
    }

    /** @return array<int, array<int, array{increase: int, decrease: int}>> */
    #[Computed]
    public function summary(): array
    {
        $summary = [];

        foreach ($this->movements() as $movement) {
            if ($movement->from_location_id !== null) {
                $summary[$movement->product_id][$movement->from_location_id] ??= ['increase' => 0, 'decrease' => 0];
                $summary[$movement->product_id][$movement->from_location_id]['decrease'] += $movement->qty;
            }

            if ($movement->to_location_id !== null) {
                $summary[$movement->product_id][$movement->to_location_id] ??= ['increase' => 0, 'decrease' => 0];
                $summary[$movement->product_id][$movement->to_location_id]['increase'] += $movement->qty;
            }
        }

        return $summary;
    }

    public function updatedPeriodBegin(): void
    {
        $this->clearComputedData();
    }

    public function updatedPeriodEnd(): void
    {
        $this->clearComputedData();
    }

    public function updatedProductSlug(): void
    {
        $this->clearComputedData();
    }

    public function updatedLocationSlug(): void
    {
        $this->clearComputedData();
    }

    public function exportExcel(): StreamedResponse
    {
        $product = $this->productSlug !== '' ? $this->products()->firstWhere('slug', $this->productSlug) : null;
        $location = $this->locationSlug !== '' ? $this->locations()->firstWhere('slug', $this->locationSlug) : null;
        $contents = app(BuildStockMovementExcel::class)->handle(
            $this->movements(),
            $this->summaryProducts(),
            $this->summaryLocations(),
            $this->summary(),
            [
                'period_begin' => $this->periodBegin !== '' ? Carbon::parse($this->periodBegin)->format('d/m/Y') : 'Awal',
                'period_end' => $this->periodEnd !== '' ? Carbon::parse($this->periodEnd)->format('d/m/Y') : 'Sekarang',
                'product' => $product ? $product->name.' — '.$product->sku : 'Semua Produk',
                'location' => $location ? $location->name.' ('.ucfirst($location->type).')' : 'Semua Lokasi',
                'exported_at' => now()->format('d/m/Y H:i:s'),
            ],
        );

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            'stock-movement-report-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function clearComputedData(): void
    {
        unset($this->movements, $this->summaryProducts, $this->summaryLocations, $this->summary);
    }

    public function render(): View
    {
        return view('livewire.stock-movement-index');
    }
}
