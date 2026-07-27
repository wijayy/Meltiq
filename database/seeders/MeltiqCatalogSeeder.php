<?php

namespace Database\Seeders;

use App\Models\Category;
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
            ['name' => 'Bolu Kering Sachet', 'sku' => 'SKU-962', 'price' => 32000],
            ['name' => 'Bolu Kering Toples', 'sku' => 'SKU-106', 'price' => 39000],
        ],
        'Cake Layer' => [
            ['name' => 'Chocolate', 'sku' => 'SKU-765', 'price' => 17000],
            ['name' => 'Matcha', 'sku' => 'SKU-590', 'price' => 17000],
            ['name' => 'Red Velvet', 'sku' => 'SKU-969', 'price' => 17000],
            ['name' => 'Taro', 'sku' => 'SKU-804', 'price' => 17000],
            ['name' => 'Tiramisu', 'sku' => 'SKU-401', 'price' => 17000],
            ['name' => 'Vanilla Almond', 'sku' => 'SKU-198', 'price' => 17000],
        ],
    ];

    public function run(): void
    {
        $warehouse = $this->defaultWarehouse();

        DB::transaction(function (): void {
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
                            'transferPrice' => $data['price'] + 5000,
                            'salePrice' => $data['price'] + 10000,
                            'isActive' => true,
                        ],
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