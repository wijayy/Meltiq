<?php

use App\Livewire\CategoryCreate;
use App\Models\Category;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(CategoryCreate::class)
        ->assertSuccessful()
        ->assertSet('title', 'Create Category');
});

it('opens the create modal and resets the form', function () {
    Livewire::test(CategoryCreate::class)
        ->set('id', 99)
        ->set('name', 'Old category')
        ->call('openCreateModal')
        ->assertSet('title', 'Create New Category')
        ->assertSet('id', null)
        ->assertSet('name', '')
        ->assertDispatched('modal-show', name: 'category-create');
});

it('loads an existing category into the edit modal', function () {
    $category = Category::factory()->create(['name' => 'Beverages']);

    Livewire::test(CategoryCreate::class)
        ->call('openEditModal', $category->id)
        ->assertSet('title', 'Edit Category')
        ->assertSet('id', $category->id)
        ->assertSet('name', 'Beverages')
        ->assertDispatched('modal-show', name: 'category-create');
});

it('creates a category and dispatches refresh events', function () {
    Livewire::test(CategoryCreate::class)
        ->set('name', 'Beverages')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSessionHas('success', 'New Category Created')
        ->assertDispatched('modal-close', name: 'category-create')
        ->assertDispatched('updateCategoryList')
        ->assertDispatched('$refresh')
        ->assertSet('id', null)
        ->assertSet('name', '');

    expect(Category::query()->where('name', 'Beverages')->exists())->toBeTrue();
});

it('updates a category', function () {
    $category = Category::factory()->create(['name' => 'Snacks']);

    Livewire::test(CategoryCreate::class)
        ->call('openEditModal', $category->id)
        ->set('name', 'Premium Snacks')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSessionHas('success', 'Category Updated')
        ->assertDispatched('updateCategoryList');

    expect($category->fresh()->name)->toBe('Premium Snacks');
});

it('requires a category name', function () {
    Livewire::test(CategoryCreate::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('rejects a category name longer than 255 characters', function () {
    Livewire::test(CategoryCreate::class)
        ->set('name', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['name' => 'max']);
});
