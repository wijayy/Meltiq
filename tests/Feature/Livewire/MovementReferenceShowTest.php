<?php

use App\Livewire\ProductionShow;
use App\Livewire\VisitShow;
use App\Models\Location;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDetail;
use Livewire\Livewire;

it('opens production and visit detail pages', function () {
    $user = User::factory()->create();
    $production = Production::factory()->create();
    $visit = Visit::factory()->create();
    ProductionDetail::factory()->for($production)->create();
    VisitDetail::factory()->for($visit)->create();

    Livewire::actingAs($user)->test(ProductionShow::class, ['production' => $production])
        ->assertSee($production->production_no);
    Livewire::actingAs($user)->test(VisitShow::class, ['visit' => $visit])
        ->assertSee($visit->visit_no);
});

it('resolves movement references to their show routes', function () {
    $product = Product::factory()->create();
    $location = Location::factory()->create();
    $productionDetail = ProductionDetail::factory()->create();
    $visitDetail = VisitDetail::factory()->create();

    $productionMovement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'to_location_id' => $location->id,
        'reference_type' => ProductionDetail::class,
        'reference_id' => $productionDetail->id,
    ])->load('reference.production');
    $visitMovement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'to_location_id' => $location->id,
        'reference_type' => VisitDetail::class,
        'reference_id' => $visitDetail->id,
    ])->load('reference.visit');

    expect($productionMovement->referenceUrl())->toBe(route('productions.show', $productionDetail->production))
        ->and($visitMovement->referenceUrl())->toBe(route('visits.show', $visitDetail->visit));
});
