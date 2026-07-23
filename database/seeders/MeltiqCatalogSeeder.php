<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MeltiqCatalogSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array{name: string, sku: string, price: int, stock: int}>>
     */
    private array $catalog = [
        'Bolu Kering' => [
            ['name' => 'Bolu Kering Sachet', 'sku' => 'SKU-962', 'price' => 32000, 'stock' => 0],
            ['name' => 'Bolu Kering Toples', 'sku' => 'SKU-106', 'price' => 39000, 'stock' => 15],
        ],
        'Cake Layer' => [
            ['name' => 'Chocolate', 'sku' => 'SKU-765', 'price' => 35000, 'stock' => 0],
            ['name' => 'Matcha', 'sku' => 'SKU-590', 'price' => 20000, 'stock' => 0],
            ['name' => 'Red Velvet', 'sku' => 'SKU-969', 'price' => 26000, 'stock' => 20],
            ['name' => 'Taro', 'sku' => 'SKU-804', 'price' => 30000, 'stock' => 0],
            ['name' => 'Tiramisu', 'sku' => 'SKU-401', 'price' => 20000, 'stock' => 0],
            ['name' => 'Vanilla Almond', 'sku' => 'SKU-198', 'price' => 24000, 'stock' => 0],
        ],
    ];

    public function run(): void
    {
        $warehouse = $this->defaultWarehouse();

        DB::transaction(function () use ($warehouse): void {
            foreach ($this->catalog as $categoryName => $products) {
                $category = Category::query()->updateOrCreate(
                    ['slug' => Str::slug($categoryName)],
                    ['name' => $categoryName, 'isActive' => true],
                );

                foreach ($products as $data) {
                    $product = Product::query()->updateOrCreate(
                        ['sku' => $data['sku']],
                        [
                            'category_id' => $category->id,
                            'name' => $data['name'],
                            'slug' => Str::slug($data['name']),
                            'description' => $data['name'],
                            'costPrice' => $data['price'],
                            'transferPrice' => $data['price'],
                            'salePrice' => $data['price'],
                            'isActive' => true,
                        ],
                    );

                    CurrentStock::query()->updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'location_id' => $warehouse->id,
                        ],
                        ['stock' => $data['stock']],
                    );
                }
            }
        });
    }

    private function defaultWarehouse(): Location
    {
        $warehouseId = (int) Setting::query()
            ->where('key', 'default_warehouse_location')
            ->value('value');

        $warehouse = Location::query()
            ->active()
            ->where('type', 'warehouse')
            ->find($warehouseId);

        if (! $warehouse) {
            throw new RuntimeException('Default warehouse harus dikonfigurasi sebelum menjalankan MeltiqCatalogSeeder.');
        }

        return $warehouse;
    }
}
