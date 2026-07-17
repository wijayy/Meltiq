<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\StockSnapshot;

#[Guarded(['id'])]
class Production extends Model
{
    /** @use HasFactory<\Database\Factories\ProductionFactory> */
    use HasFactory, Sluggable;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'production_date' => 'date',
    ];

    /** @return array{slug: array{source: string}} */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'production_no'
            ]
        ];
    }

    /** @return HasMany<ProductionDetail, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(ProductionDetail::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<Production>  $query
     * @param  array{production_no?: string, period_begin?: string, period_end?: string, created_by?: string}  $filters
     * @return Builder<Production>
     */
    public function scopeReportFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['production_no'] ?? null, fn (Builder $query, string $number) => $query->where('production_no', 'like', '%'.$number.'%'))
            ->when($filters['period_begin'] ?? null, fn (Builder $query, string $date) => $query->whereDate('production_date', '>=', $date))
            ->when($filters['period_end'] ?? null, fn (Builder $query, string $date) => $query->whereDate('production_date', '<=', $date))
            ->when($filters['created_by'] ?? null, fn (Builder $query, string $creator) => $query->whereHas(
                'creator',
                fn (Builder $query) => $query->where('name', 'like', '%'.$creator.'%'),
            ));
    }

    public function isEditable(): bool
    {
        $this->loadMissing('details.stockMovements');

        $movements = $this->details->flatMap(
            fn (ProductionDetail $detail) => $detail->stockMovements,
        );

        return ! $movements->contains(function (StockMovement $movement): bool {
            return StockSnapshot::query()
                ->where('product_id', $movement->product_id)
                ->whereIn('location_id', array_filter([$movement->from_location_id, $movement->to_location_id]))
                ->where('snapshot_date', '>=', $movement->movement_date)
                ->exists();
        });
    }

    public static function generateProductionNo(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'PRD' . $date;

        $latestNo = static::query()
            ->where('production_no', 'like', $prefix . '%')
            ->latest('id')
            ->value('production_no');

        $sequence = 1;

        if ($latestNo) {
            $sequence = (int) substr($latestNo, -3) + 1;
        }

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
