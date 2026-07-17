<?php

use App\Models\CurrentStock;
use App\Models\StockSnapshot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('stock:snapshot', function () {
    $snapshotDate = now();

    $currentStocks = CurrentStock::query()->with(['product', 'location'])->get();

    foreach ($currentStocks as $currentStock) {
        StockSnapshot::create([
            'snapshot_date' => $snapshotDate,
            'product_id' => $currentStock->product_id,
            'location_id' => $currentStock->location_id,
            'stock' => $currentStock->stock,
        ]);
    }

    $this->info('Stock snapshots created successfully.');
})->purpose('Create stock snapshots from current stock records');

Schedule::command('stock:snapshot')->dailyAt('00:00');
