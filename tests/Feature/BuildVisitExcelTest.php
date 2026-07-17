<?php

use App\Actions\BuildVisitExcel;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDetail;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('builds visit blocks followed by stock details', function () {
    $creator = User::factory()->create(['name' => 'Admin Visit']);
    $location = Location::factory()->create(['name' => 'Outlet Renon']);
    $visit = Visit::factory()->for($creator, 'creator')->for($location)->create([
        'visit_no' => 'VST20260717001',
        'visit_date' => '2026-07-17',
        'notes' => 'Kunjungan pagi',
    ]);
    $product = Product::factory()->create();
    VisitDetail::factory()->for($visit)->for($product)->create([
        'stockBefore' => 20,
        'physicalStock' => 12,
        'returnedQty' => 2,
        'expiredQty' => 3,
        'newDeliveryQty' => 10,
    ]);
    $visits = Visit::query()->with(['location', 'creator', 'details.product'])->get();

    $contents = app(BuildVisitExcel::class)->handle($visits, [
        'visit_no' => 'VST20260717',
        'location' => 'Outlet Renon',
        'period_begin' => '01/07/2026',
        'period_end' => '31/07/2026',
        'exported_at' => '17/07/2026 13:00:00',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'visit-report-').'.xlsx';
    file_put_contents($path, $contents);
    $sheet = IOFactory::load($path)->getActiveSheet();

    expect(substr($contents, 0, 2))->toBe('PK')
        ->and($sheet->getCell('A1')->getValue())->toBe('LAPORAN VISIT')
        ->and($sheet->getCell('B3')->getValue())->toBe('VST20260717')
        ->and($sheet->getCell('B4')->getValue())->toBe('Outlet Renon')
        ->and($sheet->getCell('B5')->getValue())->toBe('01/07/2026 s/d 31/07/2026')
        ->and($sheet->getCell('A10')->getValue())->toBe('VST20260717001')
        ->and($sheet->getCell('C10')->getValue())->toBe('Outlet Renon')
        ->and($sheet->getCell('J10')->getValue())->toBe(10)
        ->and($sheet->getCell('A12')->getValue())->toBe('DETAIL PRODUCT')
        ->and($sheet->getCell('B14')->getValue())->toBe($product->name)
        ->and($sheet->getCell('F14')->getValue())->toBe(5)
        ->and($sheet->getCell('J14')->getValue())->toBe(20);

    unlink($path);
});

it('builds a valid workbook when no visits match the filters', function () {
    $contents = app(BuildVisitExcel::class)->handle((new Visit)->newCollection(), [
        'visit_no' => 'Tidak ditemukan',
        'location' => 'Semua Outlet',
        'period_begin' => 'Awal',
        'period_end' => 'Sekarang',
        'exported_at' => '17/07/2026 13:00:00',
    ]);

    expect(substr($contents, 0, 2))->toBe('PK');
});
