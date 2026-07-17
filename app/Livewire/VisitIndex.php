<?php

namespace App\Livewire;

use App\Actions\BuildVisitExcel;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitIndex extends Component
{
    public string $title = 'Visits';

    #[Url(as: 'number', except: '')]
    public string $visitNo = '';

    #[Url(as: 'location', except: '')]
    public string $location = '';

    #[Url(as: 'period-begin', except: '')]
    public string $periodBegin = '';

    #[Url(as: 'period-end', except: '')]
    public string $periodEnd = '';

    /** @return Collection<int, Visit> */
    #[Computed]
    public function visits(): Collection
    {
        return Visit::query()
            ->with(['location:id,name', 'creator:id,name', 'details.product:id,name,sku', 'details.stockMovements'])
            ->withCount('details')
            ->when($this->visitNo, fn ($query) => $query->where('visit_no', 'like', '%'.$this->visitNo.'%'))
            ->when($this->location, fn ($query) => $query->whereHas('location', fn ($query) => $query->where('name', 'like', '%'.$this->location.'%')))
            ->when($this->periodBegin, fn ($query) => $query->whereDate('visit_date', '>=', $this->periodBegin))
            ->when($this->periodEnd, fn ($query) => $query->whereDate('visit_date', '<=', $this->periodEnd))
            ->latest('visit_date')
            ->latest('id')
            ->get();
    }

    public function updatedVisitNo(): void
    {
        unset($this->visits);
    }

    public function updatedLocation(): void
    {
        unset($this->visits);
    }

    public function updatedPeriodBegin(): void
    {
        unset($this->visits);
    }

    public function updatedPeriodEnd(): void
    {
        unset($this->visits);
    }

    public function exportExcel(): StreamedResponse
    {
        $contents = app(BuildVisitExcel::class)->handle($this->visits(), [
            'visit_no' => $this->visitNo !== '' ? $this->visitNo : 'Semua Nomor',
            'location' => $this->location !== '' ? $this->location : 'Semua Outlet',
            'period_begin' => $this->periodBegin !== '' ? Carbon::parse($this->periodBegin)->format('d/m/Y') : 'Awal',
            'period_end' => $this->periodEnd !== '' ? Carbon::parse($this->periodEnd)->format('d/m/Y') : 'Sekarang',
            'exported_at' => now()->format('d/m/Y H:i:s'),
        ]);

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            'visit-report-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function render(): View
    {
        return view('livewire.visit-index');
    }
}
