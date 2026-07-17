<?php

namespace App\Livewire\Settings;

use App\Actions\SaveSystemSettings;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('System settings')]
class SystemSettings extends Component
{
    public string $defaultWarehouseId = '';

    public string $expiredLocationId = '';

    public function mount(): void
    {
        $this->defaultWarehouseId = (string) Setting::query()
            ->where('key', 'default_warehouse_location')
            ->value('value');
        $this->expiredLocationId = (string) Setting::query()
            ->where('key', 'default_expired_location')
            ->value('value');
    }

    /** @return Collection<int, Location> */
    #[Computed]
    public function warehouses(): Collection
    {
        return Location::query()->active()->where('type', 'warehouse')->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, Location> */
    #[Computed]
    public function expiredLocations(): Collection
    {
        return Location::query()->active()->where('type', 'virtual')->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'defaultWarehouseId' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(fn ($query) => $query->where('type', 'warehouse')->where('isActive', true)),
            ],
            'expiredLocationId' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(fn ($query) => $query->where('type', 'virtual')->where('isActive', true)),
            ],
        ], attributes: [
            'defaultWarehouseId' => 'default warehouse',
            'expiredLocationId' => 'expired location',
        ]);

        app(SaveSystemSettings::class)->handle(
            Location::query()->findOrFail((int) $validated['defaultWarehouseId']),
            Location::query()->findOrFail((int) $validated['expiredLocationId']),
        );

        session()->flash('success', 'Konfigurasi sistem berhasil disimpan.');
    }

    public function render(): View
    {
        return view('livewire.settings.system-settings');
    }
}
