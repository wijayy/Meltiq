<?php

namespace App\Livewire;

use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class VisitShow extends Component
{
    public string $title = 'Detail Pengiriman';

    public Visit $visit;

    public function mount(Visit $visit): void
    {
        $this->visit = $visit->load(['location:id,name,type', 'creator:id,name', 'details.product:id,name,sku']);
    }

    public function render(): View
    {
        return view('livewire.visit-show');
    }
}
