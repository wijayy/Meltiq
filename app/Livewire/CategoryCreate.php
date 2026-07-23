<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CategoryCreate extends Component
{
    public $title = '';

    public $id = null;

    #[Validate('required|string|max:255')]
    public $name = '';

    public function mount()
    {
        $this->title = 'Tambah Kategori';
    }

    #[On('createCategory')]
    public function openCreateModal()
    {
        $this->title = 'Tambah Kategori Baru';
        $this->reset(['name', 'id']);
        $this->dispatch('modal-show', name: 'category-create');
    }

    #[On('editCategory')]
    public function openEditModal($id)
    {
        $category = Category::query()->active()->findOrFail($id);
        $this->title = 'Ubah Kategori';
        $this->name = $category->name;
        $this->id = $category->id;
        $this->dispatch('modal-show', name: 'category-create');
    }

    public function save()
    {
        $validated = $this->validate();
        Category::updateOrCreate(['id' => $this->id], $validated);

        $message = $this->id ? 'Kategori berhasil diperbarui.' : 'Kategori baru berhasil dibuat.';
        session()->put('success', $message);

        $this->reset(['name', 'id']);
        $this->dispatch('modal-close', name: 'category-create');
        $this->dispatch('updateCategoryList');
        $this->dispatch('$refresh');

        // $this->dispatch('category-saved', message: $message)->to('product-index');
    }

    public function render()
    {
        return view('livewire.category-create');
    }

    public function exception($e, $stopPropagation)
    {
        // if (config('app.debug')) {
        //     throw $e;
        // }
        // session()->flash('error', $e->getMessage());
        $stopPropagation();
    }
}
