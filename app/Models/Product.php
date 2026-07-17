<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Guarded(['id'])]
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, Sluggable;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'isActive' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('isActive', true);
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function scopeFilters(Builder $query, array $filters): Builder
    {
        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', $search.'%')
                    ->orWhere('sku', 'like', $search.'%');
            });
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productionDetails(): HasMany
    {
        return $this->hasMany(ProductionDetail::class);
    }

    public function visitDetails(): HasMany
    {
        return $this->hasMany(VisitDetail::class);
    }

    public function currentStocks(): HasMany
    {
        return $this->hasMany(CurrentStock::class);
    }

    public function getCurrentStockOnHandAttribute(): int
    {
        $warehouseId = (int) Setting::query()
            ->where('key', 'default_warehouse_location')
            ->value('value');

        return (int) $this->currentStocks
            ->where('location_id', $warehouseId)
            ->sum('stock');
    }

    public function stockSnapshots(): HasMany
    {
        return $this->hasMany(StockSnapshot::class);
    }
}
