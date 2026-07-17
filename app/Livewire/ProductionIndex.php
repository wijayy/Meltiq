<?php

namespace App\Livewire;

use App\Actions\BuildProductionExcel;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionIndex extends Component
{
    public string $title = 'Production';

    #[Url(as: 'number', except: '')]
    public string $productionNo = '';

    #[Url(as: 'period-begin', except: '')]
    public string $periodBegin = '';

    #[Url(as: 'period-end', except: '')]
    public string $periodEnd = '';

    #[Url(as: 'created-by', except: '')]
    public string $createdBy = '';

    /** @return Collection<int, Production> */
    #[Computed]
    public function productions(): Collection
    {
        return Production::query()
            ->with(['creator:id,name', 'details.product:id,name,sku', 'details.stockMovements'])
            ->withCount('details')
            ->withSum('details', 'qty')
            ->reportFilters($this->filters())
            ->latest('production_date')
            ->latest('id')
            ->get();
    }

    public function updatedProductionNo(): void
    {
        unset($this->productions);
    }

    public function updatedPeriodBegin(): void
    {
        unset($this->productions);
    }

    public function updatedPeriodEnd(): void
    {
        unset($this->productions);
    }

    public function updatedCreatedBy(): void
    {
        unset($this->productions);
    }

    public function exportExcel(): StreamedResponse
    {
        $contents = app(BuildProductionExcel::class)->handle($this->productions(), [
            'production_no' => $this->productionNo !== '' ? $this->productionNo : 'Semua Nomor',
            'period_begin' => $this->periodBegin !== '' ? Carbon::parse($this->periodBegin)->format('d/m/Y') : 'Awal',
            'period_end' => $this->periodEnd !== '' ? Carbon::parse($this->periodEnd)->format('d/m/Y') : 'Sekarang',
            'created_by' => $this->createdBy !== '' ? $this->createdBy : 'Semua User',
            'exported_at' => now()->format('d/m/Y H:i:s'),
        ]);

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            'production-report-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /** @return array{production_no: string, period_begin: string, period_end: string, created_by: string} */
    private function filters(): array
    {
        return [
            'production_no' => $this->productionNo,
            'period_begin' => $this->periodBegin,
            'period_end' => $this->periodEnd,
            'created_by' => $this->createdBy,
        ];
    }

    public function render(): View
    {
        return view('livewire.production-index');
    }
}
