<?php

namespace App\Actions;

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockSnapshot;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Support\Facades\DB;
use LogicException;

class SaveVisit
{
    /**
     * @param  array<int, array{product_id: int, stockBefore: int, physicalStock: int, discountQty: int, damagedQty: int, newDeliveryQty: int}>  $details
     */
    public function handle(
        User $creator,
        Location $outlet,
        Location $warehouse,
        Location $damagedLocation,
        string $visitDate,
        ?string $notes,
        array $details,
        ?Visit $visit = null,
    ): Visit {
        return DB::transaction(function () use ($creator, $outlet, $warehouse, $damagedLocation, $visitDate, $notes, $details, $visit): Visit {
            if ($visit) {
                $visit = Visit::query()->with('details.stockMovements')->lockForUpdate()->findOrFail($visit->id);
                $this->reverseExistingVisit($visit);
                $visit->update([
                    'visit_date' => $visitDate,
                    'notes' => $notes,
                    'location_id' => $outlet->id,
                ]);
            } else {
                $visit = Visit::query()->create([
                    'visit_no' => Visit::generateVisitNo(),
                    'visit_date' => $visitDate,
                    'notes' => $notes,
                    'location_id' => $outlet->id,
                    'created_by' => $creator->id,
                    'status' => 'completed',
                ]);
            }

            $requiredProductIds = CurrentStock::query()
                ->where('location_id', $outlet->id)
                ->where('stock', '>', 0)
                ->pluck('product_id');
            $submittedProductIds = collect($details)->pluck('product_id');

            if ($submittedProductIds->count() !== $submittedProductIds->unique()->count()) {
                throw new LogicException('Setiap produk hanya boleh muncul satu kali dalam pengiriman.');
            }

            if ($requiredProductIds->diff($submittedProductIds)->isNotEmpty()) {
                throw new LogicException('Semua produk yang memiliki stok di outlet wajib diisi pada pengiriman.');
            }

            foreach ($details as $detailData) {
                $this->storeDetail($visit, $outlet, $warehouse, $damagedLocation, $detailData);
            }

            return $visit->refresh();
        });
    }

    private function reverseExistingVisit(Visit $visit): void
    {
        $movements = $visit->details->flatMap(fn (VisitDetail $detail) => $detail->stockMovements);

        foreach ($movements as $movement) {
            if ($this->isCaptured($movement)) {
                throw new LogicException('Pengiriman tidak dapat diubah karena stoknya sudah masuk rekaman stok.');
            }

            if ($movement->to_location_id) {
                $this->changeStock($movement->product_id, $movement->to_location_id, -$movement->qty);
            }

            if ($movement->from_location_id) {
                $this->changeStock($movement->product_id, $movement->from_location_id, $movement->qty);
            }

            $movement->delete();
        }

        $visit->details()->delete();
    }

    /** @param array{product_id: int, stockBefore: int, physicalStock: int, discountQty: int, damagedQty: int, newDeliveryQty: int} $data */
    private function storeDetail(Visit $visit, Location $outlet, Location $warehouse, Location $damagedLocation, array $data): void
    {
        $outletStock = $this->stock($data['product_id'], $outlet->id);
        $warehouseStock = $this->stock($data['product_id'], $warehouse->id);

        if ($outletStock->stock !== $data['stockBefore']) {
            throw new LogicException('Stok outlet berubah. Muat ulang data pengiriman sebelum menyimpan.');
        }

        if ($data['stockBefore'] === 0
            && ($data['physicalStock'] !== 0 || $data['discountQty'] !== 0 || $data['damagedQty'] !== 0)) {
            throw new LogicException('Produk baru hanya dapat diisi pada new delivery qty.');
        }

        if ($data['stockBefore'] < $data['physicalStock'] + $data['damagedQty']) {
            throw new LogicException('Stok fisik dan produk rusak tidak boleh melebihi stok sebelum.');
        }

        $soldQty = $data['stockBefore'] - $data['physicalStock'] - $data['damagedQty'];

        if ($data['discountQty'] > $soldQty) {
            throw new LogicException('Jumlah diskon tidak boleh melebihi jumlah produk terjual.');
        }

        if ($data['newDeliveryQty'] > $warehouseStock->stock) {
            throw new LogicException('Stok gudang tidak mencukupi untuk pengiriman baru.');
        }

        $detail = $visit->details()->create([
            ...$data,
            'returnedQty' => 0,
            'expiredQty' => 0,
        ]);
        $detail->setRelation('visit', $visit);
        $regularSaleQty = $soldQty - $data['discountQty'];

        $this->movement($detail, 'sale', $regularSaleQty, $outlet->id, null);
        $this->movement($detail, 'discount', $data['discountQty'], $outlet->id, null, 10000, 10000);
        $this->movement($detail, 'damaged', $data['damagedQty'], $outlet->id, $damagedLocation->id);
        $this->movement($detail, 'transfer', $data['newDeliveryQty'], $warehouse->id, $outlet->id);

        $finalOutletStock = $data['physicalStock'] + $data['newDeliveryQty'];
        $outletStock->update(['stock' => $finalOutletStock]);
        $warehouseStock->decrement('stock', $data['newDeliveryQty']);

        if ($data['damagedQty'] > 0) {
            $this->changeStock($data['product_id'], $damagedLocation->id, $data['damagedQty']);
        }
    }

    private function movement(
        VisitDetail $detail,
        string $type,
        int $qty,
        ?int $from,
        ?int $to,
        ?int $transferPrice = null,
        ?int $sellPrice = null,
    ): void {
        if ($qty === 0) {
            return;
        }

        $product = Product::query()->findOrFail($detail->product_id);

        $detail->stockMovements()->create([
            'movement_date' => now(),
            'movement_type' => $type,
            'product_id' => $detail->product_id,
            'qty' => $qty,
            'unit_cost' => $product->costPrice,
            'unit_transfer_price' => $transferPrice ?? $product->transferPrice,
            'unit_sell_price' => $sellPrice ?? $product->salePrice,
            'from_location_id' => $from,
            'to_location_id' => $to,
            'reference_no' => $detail->visit->visit_no,
        ]);
    }

    private function stock(int $productId, int $locationId): CurrentStock
    {
        $stock = CurrentStock::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        return $stock ?? CurrentStock::query()->create([
            'product_id' => $productId,
            'location_id' => $locationId,
            'stock' => 0,
        ]);
    }

    private function changeStock(int $productId, int $locationId, int $change): void
    {
        $stock = $this->stock($productId, $locationId);

        if ($stock->stock + $change < 0) {
            throw new LogicException('Pengiriman tidak dapat diubah karena stok hasil transaksi sudah digunakan.');
        }

        $stock->increment('stock', $change);
    }

    private function isCaptured(StockMovement $movement): bool
    {
        return StockSnapshot::query()
            ->where('product_id', $movement->product_id)
            ->whereIn('location_id', array_filter([$movement->from_location_id, $movement->to_location_id]))
            ->where('snapshot_date', '>=', $movement->movement_date)
            ->exists();
    }
}
