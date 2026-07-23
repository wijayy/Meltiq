<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductIndex extends Component
{
    public string $title = 'Semua Produk';

    #[Url('search')]
    public string $search = '';

    #[Url(as: 'status', except: 'active')]
    public string $status = 'active';

    public ?int $deleteId = null;

    public ?string $deleteType = null;

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->when($this->status !== 'all', fn ($query) => $query->where('isActive', $this->status === 'active'))
            ->filters(['search' => $this->search])
            ->get();
    }

    /** @return Collection<int, Category> */
    #[Computed]
    #[On('updateCategoryList')]
    public function categories(): Collection
    {
        $isActive = $this->status === 'all' ? null : $this->status === 'active';

        return Category::query()
            ->when($this->status === 'active', fn ($query) => $query->active())
            ->when($this->status === 'nonactive', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('isActive', false)
                        ->orWhereHas('products', fn ($query) => $query->where('isActive', false));
                });
            })
            ->when($this->search !== '', function ($query): void {
                $query->whereHas('products', function ($query): void {
                    $query->when($this->status !== 'all', fn ($query) => $query->where('isActive', $this->status === 'active'))
                        ->where(function ($query): void {
                            $query->where('name', 'like', $this->search.'%')
                                ->orWhere('sku', 'like', $this->search.'%');
                        });
                });
            })
            ->with(['products' => function ($query) use ($isActive): void {
                $query->when($isActive !== null, fn ($query) => $query->where('isActive', $isActive))
                    ->when($this->search !== '', function ($query): void {
                        $query->where(function ($query): void {
                            $query->where('name', 'like', $this->search.'%')
                                ->orWhere('sku', 'like', $this->search.'%');
                        });
                    })
                    ->with('currentStocks')
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }

    public function updatedStatus(): void
    {
        if (! in_array($this->status, ['active', 'nonactive', 'all'], true)) {
            $this->status = 'active';
        }

        unset($this->categories, $this->products);
    }

    public function updatedSearch(): void
    {
        unset($this->categories, $this->products);
    }

    #[On('category-saved')]
    public function handleCategorySaved(?string $message = null): void
    {
        if ($message) {
            session()->put('success', $message);
        }
    }

    public function createCategory(): void
    {
        $this->dispatch('createCategory');
    }

    public function editCategory(int $id): void
    {
        $this->dispatch('editCategory', id: $id);
    }

    public function createProduct(): void
    {
        $this->dispatch('createProduct');
    }

    public function editProduct(int $id): void
    {
        $this->dispatch('editProduct', id: $id);
    }

    public function openDeleteModal(string $type, int $id): void
    {
        abort_unless(in_array($type, ['category', 'product'], true), 404);

        $this->deleteType = $type;
        $this->deleteId = $id;
        $this->dispatch('modal-show', name: 'delete-product-data');
    }

    public function closeDeleteModal(): void
    {
        $this->reset(['deleteId', 'deleteType']);
        $this->dispatch('modal-close', name: 'delete-product-data');
    }

    public function deleteData(): void
    {
        if ($this->deleteType === 'category') {
            $category = Category::query()->active()->findOrFail($this->deleteId);
            $category->update(['isActive' => false]);
            $category->products()->update(['isActive' => false]);
            $message = 'Kategori dan seluruh produk di dalamnya berhasil dinonaktifkan.';
        } elseif ($this->deleteType === 'product') {
            Product::query()->active()->findOrFail($this->deleteId)->update(['isActive' => false]);
            $message = 'Produk berhasil dinonaktifkan.';
        } else {
            abort(404);
        }

        session()->flash('success', $message);
        unset($this->categories, $this->products);
        $this->closeDeleteModal();
    }

    public function restoreCategory(int $id): void
    {
        $category = Category::query()->where('isActive', false)->findOrFail($id);
        $category->update(['isActive' => true]);
        $category->products()->update(['isActive' => true]);

        session()->flash('success', 'Kategori dan seluruh produk berhasil dipulihkan.');
        unset($this->categories, $this->products);
    }

    public function restoreProduct(int $id): void
    {
        $product = Product::query()->where('isActive', false)->findOrFail($id);
        $product->update(['isActive' => true]);
        $product->category()->update(['isActive' => true]);

        session()->flash('success', 'Produk berhasil dipulihkan.');
        unset($this->categories, $this->products);
    }

    #[On('product-saved')]
    public function handleProductSaved(string $message): void
    {
        session()->flash('success', $message);
        unset($this->categories, $this->products);
    }

    public function render(): View
    {
        return view('livewire.product-index');
    }
}
