<?php

use App\Models\CurrentStock;
use App\Models\StockSnapshot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('stock:snapshot', function () {
    $snapshotDate = now()->startOfMinute();

    $currentStocks = CurrentStock::query()->get();

    DB::transaction(function () use ($currentStocks, $snapshotDate): void {
        foreach ($currentStocks as $currentStock) {
            StockSnapshot::query()->updateOrCreate(
                [
                    'snapshot_date' => $snapshotDate,
                    'product_id' => $currentStock->product_id,
                    'location_id' => $currentStock->location_id,
                ],
                ['stock' => $currentStock->stock],
            );
        }
    });

    $this->info($currentStocks->count().' stock snapshot berhasil dibuat.');
})->purpose('Create stock snapshots from current stock records');

Schedule::command('stock:snapshot')
    ->dailyAt('00:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
