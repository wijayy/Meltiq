<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Visit extends Model
{
    /** @use HasFactory<\Database\Factories\VisitFactory> */
    use HasFactory, Sluggable;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'visit_date' => 'date',
    ];

    /** @return array{slug: array{source: string}} */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'visit_no'
            ]
        ];
    }

    /** @return HasMany<VisitDetail, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(VisitDetail::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        $this->loadMissing('details.stockMovements');

        return ! $this->details
            ->flatMap(fn (VisitDetail $detail) => $detail->stockMovements)
            ->contains(fn (StockMovement $movement): bool => StockSnapshot::query()
                ->where('product_id', $movement->product_id)
                ->whereIn('location_id', array_filter([$movement->from_location_id, $movement->to_location_id]))
                ->where('snapshot_date', '>=', $movement->movement_date)
                ->exists());
    }

    public static function generateVisitNo(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'VST' . $date;

        $latestNo = static::query()
            ->where('visit_no', 'like', $prefix . '%')
            ->latest('id')
            ->value('visit_no');

        $sequence = 1;

        if ($latestNo) {
            $sequence = (int) substr($latestNo, -3) + 1;
        }

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
