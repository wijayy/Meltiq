<?php

use App\Actions\BuildProductionExcel;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('builds production blocks followed by their product details', function () {
    $creator = User::factory()->create(['name' => 'Admin Gudang']);
    $production = Production::factory()->for($creator, 'creator')->create([
        'production_no' => 'PRD20260717001',
        'production_date' => '2026-07-17',
        'notes' => 'Produksi pagi',
    ]);
    $products = Product::factory()->count(2)->create();
    ProductionDetail::factory()->for($production)->for($products[0])->create(['qty' => 10]);
    ProductionDetail::factory()->for($production)->for($products[1])->create(['qty' => 5]);
    $productions = Production::query()->with(['creator', 'details.product'])->get();

    $contents = app(BuildProductionExcel::class)->handle($productions, [
        'production_no' => 'PRD20260717',
        'period_begin' => '01/07/2026',
        'period_end' => '31/07/2026',
        'created_by' => 'Admin Gudang',
        'exported_at' => '17/07/2026 13:00:00',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'production-report-').'.xlsx';
    file_put_contents($path, $contents);
    $sheet = IOFactory::load($path)->getActiveSheet();

    expect(substr($contents, 0, 2))->toBe('PK')
        ->and($sheet->getCell('A1')->getValue())->toBe('LAPORAN PRODUCTION')
        ->and($sheet->getCell('B3')->getValue())->toBe('PRD20260717')
        ->and($sheet->getCell('B4')->getValue())->toBe('01/07/2026 s/d 31/07/2026')
        ->and($sheet->getCell('A10')->getValue())->toBe('PRD20260717001')
        ->and($sheet->getCell('C10')->getValue())->toBe('Admin Gudang')
        ->and($sheet->getCell('E10')->getValue())->toBe(15)
        ->and($sheet->getCell('A12')->getValue())->toBe('DETAIL PRODUCT')
        ->and($sheet->getCell('B14')->getValue())->toBe($products[0]->name)
        ->and($sheet->getCell('D15')->getValue())->toBe(5);

    unlink($path);
});

it('builds a valid workbook when no productions match the filters', function () {
    $contents = app(BuildProductionExcel::class)->handle((new Production)->newCollection(), [
        'production_no' => 'Tidak ditemukan',
        'period_begin' => 'Awal',
        'period_end' => 'Sekarang',
        'created_by' => 'Semua User',
        'exported_at' => '17/07/2026 13:00:00',
    ]);

    expect(substr($contents, 0, 2))->toBe('PK');
});
