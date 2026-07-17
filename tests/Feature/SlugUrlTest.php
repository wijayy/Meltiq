<?php

use App\Livewire\StockIndex;
use App\Models\Location;
use App\Models\Product;
use App\Models\Production;

it('generates production edit urls using the slug', function () {
    $production = Production::factory()->create([
        'production_no' => 'PRD-SLUG-TEST',
    ]);

    $url = route('productions.edit', $production);

    expect($production->getRouteKey())->toBe($production->slug)
        ->and($url)->toEndWith('/productions/'.$production->slug.'/edit')
        ->and($url)->not->toContain('/productions/'.$production->id.'/edit');
});

it('filters stock with product and location slugs instead of ids', function () {
    $product = Product::factory()->create();
    $location = Location::factory()->create();
    $component = new StockIndex;

    $component->productSlug = $product->slug;
    $component->locationSlug = $location->slug;

    expect($component->productSlug)->toBe($product->slug)
        ->and($component->locationSlug)->toBe($location->slug)
        ->and($product->getRouteKey())->toBe($product->slug)
        ->and($location->getRouteKey())->toBe($location->slug);
});
