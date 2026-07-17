<?php

namespace App\Livewire;

use App\Models\Production;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProductionShow extends Component
{
    public string $title = 'Detail Production';

    public Production $production;

    public function mount(Production $production): void
    {
        $this->production = $production->load(['creator:id,name', 'details.product:id,name,sku']);
    }

    public function render(): View
    {
        return view('livewire.production-show');
    }
}
