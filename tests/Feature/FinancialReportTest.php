<?php

use App\Actions\GetFinancialReport;
use App\Livewire\FinancialReport;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Livewire\Livewire;

it('calculates sales gross profit losses and inventory values from price snapshots', function () {
    $product = Product::factory()->create([
        'costPrice' => 10000,
        'transferPrice' => 13000,
        'salePrice' => 18000,
    ]);
    $outlet = Location::factory()->create(['type' => 'outlet']);

    StockMovement::factory()->create([
        'movement_date' => now(),
        'movement_type' => 'sale',
        'product_id' => $product->id,
        'from_location_id' => $outlet->id,
        'to_location_id' => null,
        'qty' => 4,
        'unit_cost' => 9000,
        'unit_transfer_price' => 12000,
        'unit_sell_price' => 16000,
    ]);
    StockMovement::factory()->create([
        'movement_date' => now(),
        'movement_type' => 'expired',
        'product_id' => $product->id,
        'from_location_id' => $outlet->id,
        'to_location_id' => null,
        'qty' => 2,
        'unit_cost' => 9000,
    ]);
    StockMovement::factory()->create([
        'movement_date' => now(),
        'movement_type' => 'production',
        'product_id' => $product->id,
        'from_location_id' => null,
        'to_location_id' => $outlet->id,
        'qty' => 10,
        'unit_cost' => 9000,
        'unit_transfer_price' => 12000,
        'unit_sell_price' => 16000,
    ]);
    StockMovement::factory()->create([
        'movement_date' => now(),
        'movement_type' => 'transfer',
        'product_id' => $product->id,
        'from_location_id' => Location::factory()->create(['type' => 'warehouse'])->id,
        'to_location_id' => $outlet->id,
        'qty' => 3,
        'unit_cost' => 9000,
        'unit_transfer_price' => 12000,
        'unit_sell_price' => 16000,
    ]);
    CurrentStock::factory()->create([
        'product_id' => $product->id,
        'location_id' => $outlet->id,
        'stock' => 5,
    ]);

    $report = app(GetFinancialReport::class)->handle(
        now()->startOfMonth()->toDateString(),
        now()->toDateString(),
    );

    expect($report['summary'])
        ->revenue->toBe(64000)
        ->cost_of_goods_sold->toBe(36000)
        ->gross_profit->toBe(28000)
        ->loss_value->toBe(18000)
        ->net_contribution->toBe(10000)
        ->inventory_cost_value->toBe(50000)
        ->production_value->toBe(90000)
        ->transfer_value->toBe(36000)
        ->and($report['sales']->first()['outlet_margin'])->toBe(16000)
        ->and($report['productions']->first()['quantity'])->toBe(10)
        ->and($report['transfers']->first()['quantity'])->toBe(3)
        ->and($report['reconciliation']->every(fn (array $row): bool => $row['variance'] === 0))->toBeTrue();
});

it('keeps transaction values when the product master prices change', function () {
    $product = Product::factory()->create();
    $outlet = Location::factory()->create(['type' => 'outlet']);
    $movement = StockMovement::factory()->create([
        'movement_date' => now(),
        'movement_type' => 'sale',
        'product_id' => $product->id,
        'from_location_id' => $outlet->id,
        'to_location_id' => null,
        'qty' => 2,
        'unit_cost' => 5000,
        'unit_transfer_price' => 7000,
        'unit_sell_price' => 10000,
    ]);

    $product->update(['costPrice' => 50000, 'transferPrice' => 70000, 'salePrice' => 100000]);
    $movement->refresh();

    expect($movement->unit_cost)->toBe(5000)
        ->and($movement->unit_transfer_price)->toBe(7000)
        ->and($movement->unit_sell_price)->toBe(10000);
});

it('renders the financial report for an authenticated user', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(FinancialReport::class)
        ->assertOk()
        ->assertSee('Laporan Keuangan')
        ->assertSee('Omzet')
        ->assertSee('Nilai Persediaan');
});

it('downloads only selected financial report sections', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(FinancialReport::class)
        ->set('exportSections', ['summary', 'sales'])
        ->call('exportExcel')
        ->assertHasNoErrors()
        ->assertFileDownloaded('laporan-keuangan-'.now()->startOfMonth()->toDateString().'-'.now()->toDateString().'.xlsx');
});

it('requires a financial report section before exporting', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(FinancialReport::class)
        ->set('exportSections', [])
        ->call('exportExcel')
        ->assertHasErrors(['exportSections']);
});
