<?php

namespace App\Livewire;

use App\Actions\GetStockReport;
use App\Actions\BuildStockExcel;
use App\Models\Location;
use App\Models\Product;
use Carbon\Carbon;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StockIndex extends Component
{
    public string $title = 'Stock';

    #[Url(as: 'datetime', except: '')]
    public string $selectedDateTime = '';

    #[Url(as: 'product', except: '')]
    public string $productSlug = '';

    #[Url(as: 'location', except: '')]
    public string $locationSlug = '';

    /** @return Collection<int, array{product_id: int, product_name: string, sku: string, location_id: int, location_name: string, location_type: string, stock: int}> */
    #[Computed]
    public function stocks(): Collection
    {
        $at = $this->selectedDateTime !== ''
            ? Carbon::createFromFormat('Y-m-d\TH:i', $this->selectedDateTime)
            : null;

        $productId = $this->productSlug !== ''
            ? Product::query()->where('slug', $this->productSlug)->value('id')
            : null;
        $locationId = $this->locationSlug !== ''
            ? Location::query()->where('slug', $this->locationSlug)->value('id')
            : null;

        if (($this->productSlug !== '' && $productId === null)
            || ($this->locationSlug !== '' && $locationId === null)) {
            return collect();
        }

        return app(GetStockReport::class)->handle(
            at: $at,
            productId: $productId !== null ? (int) $productId : null,
            locationId: $locationId !== null ? (int) $locationId : null,
        );
    }

    /** @return EloquentCollection<int, Product> */
    #[Computed]
    public function products(): EloquentCollection
    {
        return Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'slug']);
    }

    /** @return EloquentCollection<int, Location> */
    #[Computed]
    public function locations(): EloquentCollection
    {
        return Location::query()
            ->orderByRaw("case type when 'warehouse' then 1 when 'outlet' then 2 else 3 end")
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'slug']);
    }

    public function updatedSelectedDateTime(): void
    {
        unset($this->stocks);
    }

    public function updatedProductSlug(): void
    {
        unset($this->stocks);
    }

    public function updatedLocationSlug(): void
    {
        unset($this->stocks);
    }

    public function resetDateTime(): void
    {
        $this->selectedDateTime = '';
        unset($this->stocks);
    }

    public function exportExcel(): StreamedResponse
    {
        $product = $this->productSlug !== ''
            ? Product::query()->where('slug', $this->productSlug)->first()
            : null;
        $location = $this->locationSlug !== ''
            ? Location::query()->where('slug', $this->locationSlug)->first()
            : null;
        $stockTime = $this->selectedDateTime !== ''
            ? Carbon::createFromFormat('Y-m-d\TH:i', $this->selectedDateTime)->format('d/m/Y H:i')
            : 'Saat ini ('.now()->format('d/m/Y H:i').')';
        $contents = app(BuildStockExcel::class)->handle($this->stocks(), [
            'stock_time' => $stockTime,
            'product' => $product ? $product->name.' — '.$product->sku : 'Semua Product',
            'location' => $location ? $location->name.' ('.ucfirst($location->type).')' : 'Semua Location',
            'exported_at' => now()->format('d/m/Y H:i:s'),
        ]);
        $filename = 'stock-report-'.now()->format('Ymd-His').'.xlsx';

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
        return view('livewire.stock-index');
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
