<?php

namespace App\Models;

use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Guarded(['id'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    protected $casts = [
        'movement_date' => 'datetime',
    ];

    /** @return BelongsTo<Location, $this> */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /** @return BelongsTo<Location, $this> */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function referenceUrl(): ?string
    {
        return match (true) {
            $this->reference instanceof ProductionDetail => route('productions.show', $this->reference->production),
            $this->reference instanceof VisitDetail => route('visits.show', $this->reference->visit),
            default => null,
        };
    }
}
