<?php

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockSnapshot;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDetail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

it('generates visit and production numbers in the required format', function () {
    $date = now()->format('Ymd');
    $location = Location::factory()->create();
    $user = User::factory()->create();

    Visit::create([
        'visit_no' => 'VST' . $date . '001',
        'visit_date' => now()->toDateString(),
        'location_id' => $location->id,
        'created_by' => $user->id,
        'status' => 'pending',
    ]);

    Production::create([
        'production_no' => 'PRD' . $date . '001',
        'production_date' => now()->toDateString(),
        'created_by' => $user->id,
    ]);

    expect(Visit::generateVisitNo())->toBe('VST' . $date . '002')
        ->and(Production::generateProductionNo())->toBe('PRD' . $date . '002');
});

it('exposes the expected eloquent relationships for production and visit models', function () {
    $production = new Production();
    $visit = new Visit();
    $productionDetail = new ProductionDetail();
    $visitDetail = new VisitDetail();
    $stockMovement = new StockMovement();
    $product = new Product();
    $location = new Location();
    $currentStock = new CurrentStock();
    $stockSnapshot = new StockSnapshot();
    $user = new User();

    expect($production->details())->toBeInstanceOf(HasMany::class)
        ->and($production->creator())->toBeInstanceOf(BelongsTo::class)
        ->and($productionDetail->stockMovements())->toBeInstanceOf(MorphMany::class)
        ->and($visit->details())->toBeInstanceOf(HasMany::class)
        ->and($visit->location())->toBeInstanceOf(BelongsTo::class)
        ->and($visit->creator())->toBeInstanceOf(BelongsTo::class)
        ->and($visitDetail->stockMovements())->toBeInstanceOf(MorphMany::class)
        ->and($productionDetail->production())->toBeInstanceOf(BelongsTo::class)
        ->and($productionDetail->product())->toBeInstanceOf(BelongsTo::class)
        ->and($visitDetail->visit())->toBeInstanceOf(BelongsTo::class)
        ->and($visitDetail->product())->toBeInstanceOf(BelongsTo::class)
        ->and($stockMovement->fromLocation())->toBeInstanceOf(BelongsTo::class)
        ->and($stockMovement->toLocation())->toBeInstanceOf(BelongsTo::class)
        ->and($stockMovement->product())->toBeInstanceOf(BelongsTo::class)
        ->and($stockMovement->reference())->toBeInstanceOf(MorphTo::class)
        ->and($product->productionDetails())->toBeInstanceOf(HasMany::class)
        ->and($product->visitDetails())->toBeInstanceOf(HasMany::class)
        ->and($product->currentStocks())->toBeInstanceOf(HasMany::class)
        ->and($product->stockSnapshots())->toBeInstanceOf(HasMany::class)
        ->and($location->visits())->toBeInstanceOf(HasMany::class)
        ->and($location->currentStocks())->toBeInstanceOf(HasMany::class)
        ->and($location->stockSnapshots())->toBeInstanceOf(HasMany::class)
        ->and($location->outgoingMovements())->toBeInstanceOf(HasMany::class)
        ->and($location->incomingMovements())->toBeInstanceOf(HasMany::class)
        ->and($currentStock->product())->toBeInstanceOf(BelongsTo::class)
        ->and($currentStock->location())->toBeInstanceOf(BelongsTo::class)
        ->and($stockSnapshot->product())->toBeInstanceOf(BelongsTo::class)
        ->and($stockSnapshot->location())->toBeInstanceOf(BelongsTo::class)
        ->and($user->createdProductions())->toBeInstanceOf(HasMany::class)
        ->and($user->createdVisits())->toBeInstanceOf(HasMany::class);
});
