<?php

namespace App\Livewire;

use App\Actions\CreateProduction;
use App\Actions\UpdateProduction;
use App\Models\Location;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

class ProductionCreate extends Component
{
    public string $title = 'Tambah Produksi';

    public string $productionDate = '';

    public string $notes = '';

    public ?Production $production = null;

    /** @var array<int, array{product_id: string|int, qty: string|int}> */
    public array $details = [];

    public function mount(?Production $production = null): void
    {
        if ($production?->exists) {
            abort_unless($production->isEditable(), 403, 'Produksi sudah masuk rekaman stok dan tidak dapat diubah.');

            $this->production = $production;
            $this->title = 'Ubah Produksi';
            $this->productionDate = $production->production_date->toDateString();
            $this->notes = $production->notes ?? '';
            $this->details = $production->details()
                ->get(['product_id', 'qty'])
                ->map(fn (ProductionDetail $detail): array => [
                    'product_id' => $detail->product_id,
                    'qty' => $detail->qty,
                ])
                ->all();

            return;
        }

        $this->productionDate = now()->toDateString();
        $this->addDetail();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->active()
            ->with('category:id,name')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function warehouse(): ?Location
    {
        $warehouseId = Setting::query()
            ->where('key', 'default_warehouse_location')
            ->value('value');

        $warehouse = Location::query()
            ->active()
            ->where('type', 'warehouse')
            ->when($warehouseId, fn ($query) => $query->whereKey($warehouseId))
            ->first();

        return $warehouse;
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'product_id' => '',
            'qty' => 1,
        ];
    }

    public function removeDetail(int $index): void
    {
        if (count($this->details) === 1) {
            return;
        }

        unset($this->details[$index]);
        $this->details = array_values($this->details);
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'productionDate' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('isActive', true),
            ],
            'details.*.qty' => ['required', 'integer', 'min:1'],
        ], attributes: [
            'productionDate' => 'tanggal produksi',
            'details.*.product_id' => 'product',
            'details.*.qty' => 'jumlah produksi',
        ]);

        $warehouse = $this->warehouse();

        if (! $warehouse) {
            throw new RuntimeException('Default warehouse belum dikonfigurasi atau tidak aktif.');
        }

        /** @var User $user */
        $user = auth()->user();

        if ($this->production) {
            app(UpdateProduction::class)->handle(
                production: $this->production,
                warehouse: $warehouse,
                productionDate: $validated['productionDate'],
                notes: $validated['notes'] ?: null,
                details: $validated['details'],
            );
        } else {
            app(CreateProduction::class)->handle(
                creator: $user,
                warehouse: $warehouse,
                productionDate: $validated['productionDate'],
                notes: $validated['notes'] ?: null,
                details: $validated['details'],
            );
        }

        session()->flash('success', $this->production ? 'Produksi berhasil diubah.' : 'Produksi berhasil disimpan dan stok gudang telah diperbarui.');
        $this->redirectRoute('productions.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.production-create');
    }
}
