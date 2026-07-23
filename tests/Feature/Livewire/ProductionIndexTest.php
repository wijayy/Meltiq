<?php

use App\Livewire\ProductionIndex;
use App\Models\Production;
use App\Models\ProductionDetail;
use Livewire\Livewire;

it('renders productions with creator and aggregate details', function () {
    $production = Production::factory()->create();
    ProductionDetail::factory()->count(2)->for($production)->create(['qty' => 5]);

    Livewire::test(ProductionIndex::class)
        ->assertSuccessful()
        ->assertSee($production->production_no)
        ->assertSee('10 Pcs');
});

it('searches production by production number', function () {
    $matched = Production::factory()->create(['production_no' => 'PRD20260717001']);
    $notMatched = Production::factory()->create(['production_no' => 'PRD20260718001']);

    Livewire::test(ProductionIndex::class)
        ->set('productionNo', '20260717')
        ->assertSee($matched->production_no)
        ->assertDontSee($notMatched->production_no);
});
