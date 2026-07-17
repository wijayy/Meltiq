<?php

namespace App\Livewire;

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\Production;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public string $title = 'Dashboard';

    /** @return array{products: int, locations: int, total_stock: int, users: int, movements_today: int} */
    #[Computed]
    public function metrics(): array
    {
        return [
            'products' => Product::query()->active()->count(),
            'locations' => Location::query()->active()->count(),
            'total_stock' => (int) CurrentStock::query()->sum('stock'),
            'users' => User::query()->count(),
            'movements_today' => StockMovement::query()->whereDate('movement_date', today())->count(),
        ];
    }

    /** @return array{warehouse: int, outlet: int, virtual: int} */
    #[Computed]
    public function stockByLocationType(): array
    {
        $stocks = CurrentStock::query()
            ->join('locations', 'locations.id', '=', 'current_stocks.location_id')
            ->selectRaw('locations.type, SUM(current_stocks.stock) as total_stock')
            ->groupBy('locations.type')
            ->pluck('total_stock', 'type');

        return [
            'warehouse' => (int) ($stocks['warehouse'] ?? 0),
            'outlet' => (int) ($stocks['outlet'] ?? 0),
            'virtual' => (int) ($stocks['virtual'] ?? 0),
        ];
    }

    /** @return Collection<int, CurrentStock> */
    #[Computed]
    public function lowStocks(): Collection
    {
        return CurrentStock::query()
            ->with(['product:id,name,sku,slug', 'location:id,name,type,slug'])
            ->whereBetween('stock', [1, 10])
            ->orderBy('stock')
            ->limit(6)
            ->get();
    }

    /** @return Collection<int, Production> */
    #[Computed]
    public function recentProductions(): Collection
    {
        return Production::query()
            ->with('creator:id,name')
            ->withSum('details', 'qty')
            ->latest('production_date')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    /** @return Collection<int, Visit> */
    #[Computed]
    public function recentVisits(): Collection
    {
        return Visit::query()
            ->with(['location:id,name', 'creator:id,name'])
            ->withCount('details')
            ->latest('visit_date')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
