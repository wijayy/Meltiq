<?php

use App\Actions\BuildFinancialReportExcel;
use PhpOffice\PhpSpreadsheet\IOFactory;

function financialReportFixture(): array
{
    return [
        'summary' => [
            'revenue' => 100000,
            'cost_of_goods_sold' => 60000,
            'gross_profit' => 40000,
            'gross_margin' => 40,
            'production_value' => 90000,
            'transfer_value' => 80000,
            'loss_value' => 5000,
            'net_contribution' => 35000,
            'inventory_cost_value' => 200000,
        ],
        'sales' => collect([[
            'product' => 'Bolu Kering',
            'sku' => 'SKU-001',
            'quantity' => 5,
            'revenue' => 100000,
            'cost_of_goods_sold' => 60000,
            'gross_profit' => 40000,
            'outlet_margin' => 25000,
        ]]),
        'productions' => collect(),
        'transfers' => collect(),
        'losses' => collect(),
        'inventory' => collect(),
        'reconciliation' => collect(),
    ];
}

it('exports only the selected financial report sections', function () {
    $contents = app(BuildFinancialReportExcel::class)->handle(
        financialReportFixture(),
        ['summary', 'sales'],
        [
            'period' => '01/07/2026 s/d 31/07/2026',
            'product' => 'Semua Produk',
            'location' => 'Semua Lokasi',
            'exported_at' => '27/07/2026 15:00:00',
        ],
    );
    $path = tempnam(sys_get_temp_dir(), 'financial-report-').'.xlsx';
    file_put_contents($path, $contents);
    $spreadsheet = IOFactory::load($path);

    expect(substr($contents, 0, 2))->toBe('PK')
        ->and($spreadsheet->getSheetNames())->toBe(['Ringkasan', 'Penjualan'])
        ->and($spreadsheet->getSheetByName('Produksi'))->toBeNull()
        ->and($spreadsheet->getSheetByName('Ringkasan')->getCell('B9')->getValue())->toBe(100000)
        ->and($spreadsheet->getSheetByName('Ringkasan')->getCell('A10')->getValue())->toBe('Nilai Terjual')
        ->and($spreadsheet->getSheetByName('Penjualan')->getCell('A9')->getValue())->toBe('Bolu Kering')
        ->and($spreadsheet->getSheetByName('Penjualan')->getCell('G9')->getValue())->toBe(25000);

    unlink($path);
});

it('requires at least one valid export section', function () {
    app(BuildFinancialReportExcel::class)->handle(
        financialReportFixture(),
        [],
        [
            'period' => '-',
            'product' => '-',
            'location' => '-',
            'exported_at' => '-',
        ],
    );
})->throws(InvalidArgumentException::class, 'Pilih minimal satu bagian laporan');
