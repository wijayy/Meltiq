<?php

use App\Livewire\ProductCreate;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

function validProductData(Category $category): array
{
    return [
        'categoryId' => $category->id,
        'name' => 'Keripik Pisang',
        'sku' => 'KP-001',
        'description' => 'Keripik pisang untuk produk konsinyasi.',
        'costPrice' => 10000,
        'transferPrice' => 12000,
        'salePrice' => 15000,
        'isActive' => true,
    ];
}

it('renders successfully', function () {
    Livewire::test(ProductCreate::class)
        ->assertSuccessful();
});

it('opens the create modal with clean defaults', function () {
    Livewire::test(ProductCreate::class)
        ->set('productId', 99)
        ->set('name', 'Old name')
        ->set('isActive', false)
        ->call('openCreateModal')
        ->assertSet('title', 'Tambah Product')
        ->assertSet('productId', null)
        ->assertSet('name', '')
        ->assertSet('costPrice', 0)
        ->assertSet('transferPrice', 0)
        ->assertSet('salePrice', 0)
        ->assertSet('isActive', true)
        ->assertDispatched('modal-show', name: 'product-create');
});

it('loads an existing product into the edit modal', function () {
    $product = Product::factory()->create(['isActive' => false]);

    Livewire::test(ProductCreate::class)
        ->call('openEditModal', $product->id)
        ->assertSet('title', 'Edit Product')
        ->assertSet('productId', $product->id)
        ->assertSet('categoryId', $product->category_id)
        ->assertSet('name', $product->name)
        ->assertSet('sku', $product->sku)
        ->assertSet('description', $product->description)
        ->assertSet('costPrice', $product->costPrice)
        ->assertSet('transferPrice', $product->transferPrice)
        ->assertSet('salePrice', $product->salePrice)
        ->assertSet('isActive', false)
        ->assertDispatched('modal-show', name: 'product-create');
});

it('creates a product and dispatches refresh events', function () {
    $category = Category::factory()->create();

    $component = Livewire::test(ProductCreate::class);

    foreach (validProductData($category) as $property => $value) {
        $component->set($property, $value);
    }

    $component->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('modal-close', name: 'product-create')
        ->assertDispatched('product-saved', message: 'Product berhasil ditambahkan.', categoryId: $category->id)
        ->assertSet('productId', null)
        ->assertSet('name', '');

    $product = Product::query()->where('sku', 'KP-001')->first();

    expect($product)->not->toBeNull()
        ->and($product->category_id)->toBe($category->id)
        ->and($product->name)->toBe('Keripik Pisang')
        ->and($product->isActive)->toBeTruthy();
});

it('updates a product without rejecting its current sku', function () {
    $product = Product::factory()->create(['sku' => 'KP-001']);

    Livewire::test(ProductCreate::class)
        ->call('openEditModal', $product->id)
        ->set('name', 'Keripik Pisang Premium')
        ->set('salePrice', 18000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('product-saved', message: 'Product berhasil diperbarui.', categoryId: $product->category_id);

    expect($product->fresh()->name)->toBe('Keripik Pisang Premium')
        ->and($product->fresh()->salePrice)->toBe(18000);
});

it('requires all mandatory product fields', function (string $property) {
    $category = Category::factory()->create();
    $data = validProductData($category);
    $data[$property] = $property === 'categoryId' ? null : '';

    $component = Livewire::test(ProductCreate::class);

    foreach ($data as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('save')->assertHasErrors([$property => 'required']);
})->with([
    'category' => 'categoryId',
    'name' => 'name',
    'sku' => 'sku',
    'description' => 'description',
    'cost price' => 'costPrice',
    'transfer price' => 'transferPrice',
    'sale price' => 'salePrice',
]);

it('rejects a category that does not exist', function () {
    Livewire::test(ProductCreate::class)
        ->set('categoryId', 999999)
        ->set('name', 'Product')
        ->set('sku', 'SKU-001')
        ->set('description', 'Description')
        ->set('costPrice', 1000)
        ->set('transferPrice', 1200)
        ->set('salePrice', 1500)
        ->call('save')
        ->assertHasErrors(['categoryId' => 'exists']);
});

it('rejects an inactive category', function () {
    $category = Category::factory()->create(['isActive' => false]);
    $component = Livewire::test(ProductCreate::class);

    foreach (validProductData($category) as $property => $value) {
        $component->set($property, $value);
    }

    $component->call('save')
        ->assertHasErrors(['categoryId' => 'exists']);
});

it('rejects a duplicate sku', function () {
    $product = Product::factory()->create(['sku' => 'DUPLICATE']);
    $category = Category::factory()->create();
    $component = Livewire::test(ProductCreate::class);

    foreach (validProductData($category) as $property => $value) {
        $component->set($property, $value);
    }

    $component->set('sku', $product->sku)
        ->call('save')
        ->assertHasErrors(['sku' => 'unique']);
});

it('rejects invalid prices', function (string $property) {
    $category = Category::factory()->create();
    $component = Livewire::test(ProductCreate::class);

    foreach (validProductData($category) as $field => $value) {
        $component->set($field, $value);
    }

    $component->set($property, -1)
        ->call('save')
        ->assertHasErrors([$property => 'min']);
})->with([
    'cost price' => 'costPrice',
    'transfer price' => 'transferPrice',
    'sale price' => 'salePrice',
]);

it('closes the modal and resets the form', function () {
    Livewire::test(ProductCreate::class)
        ->set('name', 'Unsaved product')
        ->set('isActive', false)
        ->call('closeModal')
        ->assertSet('name', '')
        ->assertSet('isActive', true)
        ->assertDispatched('modal-close', name: 'product-create');
});
