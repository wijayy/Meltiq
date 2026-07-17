<?php

namespace App\Livewire;

use App\Models\Location;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class LocationIndex extends Component
{
    public string $title = 'Locations';

    public ?int $locationId = null;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'active')]
    public string $status = 'active';

    /** @return Collection<int, Location> */
    #[Computed]
    public function locations(): Collection
    {
        return Location::query()
            ->when($this->status !== 'all', fn ($query) => $query->where('isActive', $this->status === 'active'))
            ->when($this->search, function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('type', 'like', '%'.$this->search.'%');
            })
            ->orderByRaw("case type when 'warehouse' then 1 when 'outlet' then 2 else 3 end")
            ->orderBy('name')
            ->get();
    }

    /** @return array<int, int> */
    #[Computed]
    public function systemLocationIds(): array
    {
        return Setting::query()
            ->whereIn('key', ['default_warehouse_location', 'default_expired_location'])
            ->pluck('value')
            ->map(fn (string $value): int => (int) $value)
            ->all();
    }

    public function updatedStatus(): void
    {
        if (! in_array($this->status, ['active', 'nonactive', 'all'], true)) {
            $this->status = 'active';
        }

        unset($this->locations);
    }

    public function updatedSearch(): void
    {
        unset($this->locations);
    }

    public function createLocation(): void
    {
        $this->dispatch('createLocation');
    }

    public function editLocation(int $id): void
    {
        $this->dispatch('editLocation', id: $id);
    }

    public function openDeleteModal(int $id): void
    {
        $this->locationId = $id;
        $this->dispatch('modal-show', name: 'delete-location');
    }

    public function closeDeleteModal(): void
    {
        $this->reset('locationId');
        $this->dispatch('modal-close', name: 'delete-location');
    }

    public function deleteLocation(): void
    {
        $location = Location::query()->active()->findOrFail($this->locationId);

        if (! $location->canDeactivate()) {
            session()->flash('error', $location->isSystemLocation()
                ? 'Location digunakan oleh konfigurasi sistem dan tidak dapat dinonaktifkan.'
                : 'Minimal satu location untuk tipe ini harus tetap aktif.');

            $this->closeDeleteModal();

            return;
        }

        $location->update(['isActive' => false]);

        session()->flash('success', 'Outlet berhasil dinonaktifkan.');
        $this->closeDeleteModal();
    }

    public function restoreLocation(int $id): void
    {
        Location::query()
            ->where('isActive', false)
            ->findOrFail($id)
            ->update(['isActive' => true]);

        session()->flash('success', 'Location berhasil direstore.');
        unset($this->locations);
    }

    #[On('location-saved')]
    public function refreshLocations(string $message): void
    {
        session()->flash('success', $message);
        unset($this->locations);
    }

    public function render(): View
    {
        return view('livewire.location-index');
    }
}
