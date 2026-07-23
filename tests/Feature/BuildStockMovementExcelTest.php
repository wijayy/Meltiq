<?php

use App\Actions\BuildStockMovementExcel;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('exports movement details and the product location summary matrix', function () {
    $product = Product::factory()->create();
    $warehouse = Location::factory()->create(['name' => 'Warehouse Utama']);
    $outlet = Location::factory()->create(['name' => 'Outlet Renon']);
    $expired = Location::factory()->create(['name' => 'Expired', 'type' => 'virtual']);
    $movement = StockMovement::factory()->create([
        'movement_date' => '2026-07-17 10:00:00',
        'movement_type' => 'transfer',
        'product_id' => $product->id,
        'from_location_id' => $warehouse->id,
        'to_location_id' => $outlet->id,
        'qty' => 10,
        'reference_no' => 'VST001',
    ]);
    $movements = StockMovement::query()->with(['product', 'fromLocation', 'toLocation'])->get();
    $products = collect([$product]);
    $locations = collect([$outlet, $warehouse, $expired]);

    $contents = app(BuildStockMovementExcel::class)->handle(
        $movements,
        $products,
        $locations,
        [$product->id => [
            $warehouse->id => ['increase' => 0, 'decrease' => 10],
            $outlet->id => ['increase' => 10, 'decrease' => 0],
            $expired->id => ['increase' => 3, 'decrease' => 0],
        ]],
        [
            'period_begin' => '01/07/2026',
            'period_end' => '31/07/2026',
            'product' => 'Semua Product',
            'location' => 'Semua Location',
            'exported_at' => '17/07/2026 13:00:00',
        ],
    );

    $path = tempnam(sys_get_temp_dir(), 'stock-movement-report-').'.xlsx';
    file_put_contents($path, $contents);
    $sheet = IOFactory::load($path)->getActiveSheet();

    expect(substr($contents, 0, 2))->toBe('PK')
        ->and($sheet->getCell('A1')->getValue())->toBe('LAPORAN PERGERAKAN STOK')
        ->and($sheet->getCell('C3')->getValue())->toBe('01/07/2026 s/d 31/07/2026')
        ->and($sheet->getCell('C10')->getValue())->toContain($product->name)
        ->and($sheet->getCell('G10')->getValue())->toBe($movement->qty)
        ->and($sheet->getCell('B13')->getValue())->toBe('RANGKUMAN PERGERAKAN PER PRODUK DAN LOKASI')
        ->and($sheet->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('4E2011')
        ->and($sheet->getStyle('A1')->getFont()->getColor()->getRGB())->toBe('FFFFFF')
        ->and($sheet->getCell('C14')->getValue())->toBe('Outlet Renon')
        ->and($sheet->getCell('E14')->getValue())->toBe('Warehouse Utama')
        ->and($sheet->getCell('C15')->getValue())->toBe('Bertambah')
        ->and($sheet->getCell('D15')->getValue())->toBe('Berkurang')
        ->and($sheet->getCell('C16')->getValue())->toBe(10)
        ->and($sheet->getCell('D16')->getValue())->toBe(0)
        ->and($sheet->getCell('E16')->getValue())->toBe(0)
        ->and($sheet->getCell('F16')->getValue())->toBe(10)
        ->and($sheet->getCell('G14')->getValue())->toBe('Expired')
        ->and($sheet->getCell('G15')->getValue())->toBe('Bertambah')
        ->and($sheet->getCell('G16')->getValue())->toBe(3);

    unlink($path);
});
