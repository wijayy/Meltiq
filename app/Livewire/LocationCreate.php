<?php

namespace App\Livewire;

use App\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class LocationCreate extends Component
{
    public string $title = 'Tambah Lokasi';

    public ?int $locationId = null;

    public string $name = '';

    public string $type = 'outlet';

    #[On('createLocation')]
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->title = 'Tambah Lokasi';
        $this->dispatch('modal-show', name: 'location-create');
    }

    #[On('editLocation')]
    public function openEditModal(int $id): void
    {
        $location = Location::query()->active()->findOrFail($id);

        $this->resetValidation();
        $this->title = 'Ubah Lokasi';
        $this->locationId = $location->id;
        $this->name = $location->name;
        $this->type = $location->type;
        $this->dispatch('modal-show', name: 'location-create');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('locations', 'name')->ignore($this->locationId)],
            'type' => ['required', Rule::in(['warehouse', 'outlet', 'virtual'])],
        ]);

        $location = $this->locationId
            ? Location::query()->findOrFail($this->locationId)
            : null;

        if ($location
            && $location->type !== $validated['type']
            && $location->isSystemLocation()) {
            throw ValidationException::withMessages([
                'type' => 'Tipe location tidak dapat diubah karena sedang digunakan oleh konfigurasi sistem.',
            ]);
        }

        if ($location
            && in_array($location->type, ['warehouse', 'virtual'], true)
            && $location->type !== $validated['type']
            && Location::query()->active()->where('type', $location->type)->count() <= 1) {
            throw ValidationException::withMessages([
                'type' => $location->type === 'warehouse'
                    ? 'Minimal satu warehouse harus tetap tersedia.'
                    : 'Lokasi virtual expired harus tetap tersedia.',
            ]);
        }

        Location::query()->updateOrCreate(
            ['id' => $this->locationId],
            $validated,
        );

        $message = $this->locationId
            ? 'Lokasi berhasil diperbarui.'
            : 'Lokasi berhasil ditambahkan.';

        $this->dispatch('modal-close', name: 'location-create');
        $this->dispatch('location-saved', message: $message);
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->dispatch('modal-close', name: 'location-create');
    }

    private function resetForm(): void
    {
        $this->reset(['locationId', 'name']);
        $this->type = 'outlet';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.location-create');
    }
}
