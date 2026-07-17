<?php

use App\Actions\BuildStockExcel;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('builds an xlsx stock report containing filters and stock rows', function () {
    $contents = app(BuildStockExcel::class)->handle(new Collection([
        [
            'product_id' => 1,
            'product_name' => 'Kopi Arabika',
            'sku' => 'KOPI-001',
            'location_id' => 2,
            'location_name' => 'Warehouse Utama',
            'location_type' => 'warehouse',
            'stock' => 25,
        ],
    ]), [
        'stock_time' => '17/07/2026 12:00',
        'product' => 'Kopi Arabika — KOPI-001',
        'location' => 'Warehouse Utama (Warehouse)',
        'exported_at' => '17/07/2026 13:00:00',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'stock-report-').'.xlsx';
    file_put_contents($path, $contents);
    $sheet = IOFactory::load($path)->getActiveSheet();

    expect(substr($contents, 0, 2))->toBe('PK')
        ->and($sheet->getCell('A1')->getValue())->toBe('LAPORAN STOCK')
        ->and($sheet->getCell('B3')->getValue())->toBe('17/07/2026 12:00')
        ->and($sheet->getCell('B4')->getValue())->toBe('Kopi Arabika — KOPI-001')
        ->and($sheet->getCell('B5')->getValue())->toBe('Warehouse Utama (Warehouse)')
        ->and($sheet->getCell('B10')->getValue())->toBe('Kopi Arabika')
        ->and($sheet->getCell('E10')->getValue())->toBe(25);

    unlink($path);
});

it('builds a valid xlsx report when filters return no stock rows', function () {
    $contents = app(BuildStockExcel::class)->handle(collect(), [
        'stock_time' => 'Saat ini (17/07/2026 12:00)',
        'product' => 'Semua Product',
        'location' => 'Semua Location',
        'exported_at' => '17/07/2026 12:00:00',
    ]);

    expect(substr($contents, 0, 2))->toBe('PK');
});
