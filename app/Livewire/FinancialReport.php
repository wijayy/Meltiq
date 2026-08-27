<?php

namespace App\Livewire;

use App\Actions\BuildFinancialReportExcel;
use App\Actions\GetFinancialReport;
use App\Models\Location;
use App\Models\Product;
use App\Models\Setting;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class FinancialReport extends Component
{
    public string $title = 'Laporan Keuangan';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'product', except: '')]
    public string $productSlug = '';

    #[Url(as: 'location', except: '')]
    public string $locationSlug = '';

    #[Url(as: 'status', except: '')]
    public string $movementStatus = '';

    /** @var array<int, string> */
    public array $exportSections = ['summary', 'sales', 'losses'];

    // BEGIN KODE INTI SKRIPSI: FUNGSI INTI LIVEWIRE
    public function mount(): void
    {
        $this->dateFrom = $this->dateFrom ?: now()->startOfMonth()->toDateString();
        $this->dateTo = $this->dateTo ?: now()->toDateString();
        $this->normalizeMovementStatus();
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function report(): array
    {
        $productId = $this->productSlug !== ''
            ? Product::query()->where('slug', $this->productSlug)->value('id')
            : null;
        $locationId = $this->locationSlug !== ''
            ? Location::query()->where('slug', $this->locationSlug)->value('id')
            : null;

        return app(GetFinancialReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            $productId !== null ? (int) $productId : null,
            $locationId !== null ? (int) $locationId : null,
            $this->movementStatus,
        );
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'slug']);
    }

    // END KODE INTI SKRIPSI: FUNGSI INTI LIVEWIRE

    /** @return Collection<int, Location> */
    #[Computed]
    public function locations(): Collection
    {
        $damagedLocationId = Setting::query()
            ->where('key', 'default_damaged_location')
            ->value('value');

        return Location::query()
            ->when($damagedLocationId, fn ($query, string $id) => $query->whereKeyNot((int) $id))
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'slug']);
    }

    public function updatedMovementStatus(): void
    {
        $this->normalizeMovementStatus();
        unset($this->report);
    }

    private function normalizeMovementStatus(): void
    {
        if (! in_array($this->movementStatus, ['', 'damaged', 'expired', 'return'], true)) {
            $this->movementStatus = '';
        }
    }

    public function exportExcel(): ?StreamedResponse
    {
        $allowedSections = ['summary', 'sales', 'productions', 'transfers', 'losses', 'inventory', 'reconciliation'];
        $sections = array_values(array_intersect($allowedSections, $this->exportSections));

        if ($sections === []) {
            $this->addError('exportSections', 'Pilih minimal satu bagian laporan.');

            return null;
        }

        $product = $this->productSlug !== ''
            ? Product::query()->where('slug', $this->productSlug)->first()
            : null;
        $location = $this->locationSlug !== ''
            ? Location::query()->where('slug', $this->locationSlug)->first()
            : null;
        $contents = app(BuildFinancialReportExcel::class)->handle(
            $this->report(),
            $sections,
            [
                'period' => $this->dateFrom.' s/d '.$this->dateTo,
                'product' => $product ? $product->name.' — '.$product->sku : 'Semua Produk',
                'location' => $location ? $location->name : 'Semua Lokasi',
                'exported_at' => now()->format('d/m/Y H:i:s'),
            ],
        );
        $filename = 'laporan-keuangan-'.$this->dateFrom.'-'.$this->dateTo.'.xlsx';

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function render(): View
    {
        return view('livewire.financial-report');
    }

    public function exception(Throwable $exception, Closure $stopPropagation): void
    {
        if ($exception instanceof ValidationException) {
            return;
        }

        if (config('app.debug')) {
            throw $exception;
        }

        report($exception);

        $message = match (true) {
            $exception instanceof ModelNotFoundException => 'Data yang diminta tidak ditemukan.',
            $exception instanceof AuthorizationException => 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
            $exception instanceof QueryException => 'Terjadi kesalahan saat memproses data di database.',
            $exception instanceof HttpExceptionInterface => match ($exception->getStatusCode()) {
                403 => 'Anda tidak memiliki akses ke halaman atau tindakan ini.',
                404 => 'Halaman atau data yang diminta tidak ditemukan.',
                419 => 'Sesi Anda telah berakhir. Silakan muat ulang halaman.',
                429 => 'Terlalu banyak permintaan. Silakan coba kembali beberapa saat lagi.',
                default => 'Permintaan tidak dapat diproses.',
            },
            default => 'Terjadi kesalahan. Silakan coba kembali atau hubungi administrator.',
        };

        session()->flash('error', $message);
        $stopPropagation();
    }
}
