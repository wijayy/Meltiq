<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductCreate extends Component
{
    public string $title = 'Tambah Produk';

    public ?int $productId = null;

    public ?int $categoryId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int|string $costPrice = 0;

    public int|string $transferPrice = 0;

    public int|string $salePrice = 0;

    public bool $isActive = true;

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->active()->orderBy('name')->get();
    }

    #[On('createProduct')]
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->title = 'Tambah Produk';
        $this->dispatch('modal-show', name: 'product-create');
    }

    #[On('editProduct')]
    public function openEditModal(int $id): void
    {
        $product = Product::query()->active()->findOrFail($id);

        $this->resetValidation();
        $this->title = 'Ubah Produk';
        $this->productId = $product->id;
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description;
        $this->costPrice = $product->costPrice;
        $this->transferPrice = $product->transferPrice;
        $this->salePrice = $product->salePrice;
        $this->isActive = $product->isActive;
        $this->dispatch('modal-show', name: 'product-create');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'categoryId' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('isActive', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($this->productId)],
            'description' => ['required', 'string'],
            'costPrice' => ['required', 'integer', 'min:0'],
            'transferPrice' => ['required', 'integer', 'min:0'],
            'salePrice' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        $product = Product::query()->updateOrCreate(
            ['id' => $this->productId],
            [
                'category_id' => $validated['categoryId'],
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'description' => $validated['description'],
                'costPrice' => $validated['costPrice'],
                'transferPrice' => $validated['transferPrice'],
                'salePrice' => $validated['salePrice'],
                'isActive' => $validated['isActive'],
            ],
        );

        $message = $this->productId
            ? 'Produk berhasil diperbarui.'
            : 'Produk berhasil ditambahkan.';

        $this->dispatch('modal-close', name: 'product-create');
        $this->dispatch('product-saved', message: $message, categoryId: $product->category_id);
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->dispatch('modal-close', name: 'product-create');
    }

    private function resetForm(): void
    {
        $this->reset([
            'productId',
            'categoryId',
            'name',
            'sku',
            'description',
        ]);
        $this->costPrice = 0;
        $this->transferPrice = 0;
        $this->salePrice = 0;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.product-create');
    }
}
