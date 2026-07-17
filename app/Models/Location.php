<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
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

    /**
     * @param  Builder<Location>  $query
     * @return Builder<Location>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('isActive', true);
    }

    public function isSystemLocation(): bool
    {
        return Setting::query()
            ->whereIn('key', ['default_warehouse_location', 'default_expired_location'])
            ->where('value', (string) $this->id)
            ->exists();
    }

    public function canDeactivate(): bool
    {
        if ($this->isSystemLocation()) {
            return false;
        }

        if (! in_array($this->type, ['warehouse', 'virtual'], true)) {
            return true;
        }

        return static::query()->active()->where('type', $this->type)->whereKeyNot($this->id)->exists();
    }

    /** @return array{slug: array{source: string}} */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /** @return HasMany<Visit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** @return HasMany<CurrentStock, $this> */
    public function currentStocks(): HasMany
    {
        return $this->hasMany(CurrentStock::class);
    }

    /** @return HasMany<StockSnapshot, $this> */
    public function stockSnapshots(): HasMany
    {
        return $this->hasMany(StockSnapshot::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    /** @return HasMany<StockMovement, $this> */
    public function incomingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }
}
