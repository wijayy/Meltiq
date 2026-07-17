<?php

use App\Models\Production;
use App\Models\User;

it('filters productions by number period and creator name', function () {
    $matchedCreator = User::factory()->create(['name' => 'Admin Gudang']);
    $otherCreator = User::factory()->create(['name' => 'Kasir Outlet']);
    $matched = Production::factory()->for($matchedCreator, 'creator')->create([
        'production_no' => 'PRD20260717001',
        'production_date' => '2026-07-17',
    ]);
    Production::factory()->for($otherCreator, 'creator')->create([
        'production_no' => 'PRD20260717002',
        'production_date' => '2026-07-17',
    ]);
    Production::factory()->for($matchedCreator, 'creator')->create([
        'production_no' => 'PRD20260801001',
        'production_date' => '2026-08-01',
    ]);

    $results = Production::query()->reportFilters([
        'production_no' => '202607',
        'period_begin' => '2026-07-01',
        'period_end' => '2026-07-31',
        'created_by' => 'Gudang',
    ])->get();

    expect($results)->toHaveCount(1)
        ->and($results->sole()->is($matched))->toBeTrue();
});
