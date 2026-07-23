<?php

use App\Livewire\ProductIndex;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

it('deactivates a product without deleting it', function () {
    $product = Product::factory()->create(['isActive' => true]);

    Livewire::test(ProductIndex::class)
        ->set('deleteType', 'product')
        ->set('deleteId', $product->id)
        ->call('deleteData')
        ->assertSee('Produk berhasil dinonaktifkan.');

    expect($product->fresh())->not->toBeNull()
        ->and($product->fresh()->isActive)->toBeFalse();
});

it('deactivates a category and all of its active products', function () {
    $category = Category::factory()->create(['isActive' => true]);
    $products = Product::factory()->count(2)->for($category)->create(['isActive' => true]);

    Livewire::test(ProductIndex::class)
        ->set('deleteType', 'category')
        ->set('deleteId', $category->id)
        ->call('deleteData')
        ->assertSee('Kategori dan seluruh produk di dalamnya berhasil dinonaktifkan.');

    expect($category->fresh()->isActive)->toBeFalse()
        ->and($products->every(fn (Product $product): bool => $product->fresh()->isActive === false))->toBeTrue();
});

it('does not display inactive categories or products', function () {
    $activeCategory = Category::factory()->create(['isActive' => true]);
    $inactiveCategory = Category::factory()->create(['isActive' => false]);
    $activeProduct = Product::factory()->for($activeCategory)->create(['isActive' => true]);
    $inactiveProduct = Product::factory()->for($activeCategory)->create(['isActive' => false]);

    Livewire::test(ProductIndex::class)
        ->assertSee($activeCategory->name)
        ->assertSee($activeProduct->name)
        ->assertDontSee($inactiveCategory->name)
        ->assertDontSee($inactiveProduct->name);
});

it('rejects an unsupported delete type', function () {
    Livewire::test(ProductIndex::class)
        ->call('openDeleteModal', 'unsupported', 1)
        ->assertNotFound();
});

it('restores a product and its inactive category', function () {
    $category = Category::factory()->create(['isActive' => false]);
    $product = Product::factory()->for($category)->create(['isActive' => false]);

    Livewire::test(ProductIndex::class)
        ->call('restoreProduct', $product->id)
        ->assertSee('Produk berhasil dipulihkan.');

    expect($product->fresh()->isActive)->toBeTrue()
        ->and($category->fresh()->isActive)->toBeTrue();
});

it('restores a category and all of its products', function () {
    $category = Category::factory()->create(['isActive' => false]);
    $products = Product::factory()->count(2)->for($category)->create(['isActive' => false]);

    Livewire::test(ProductIndex::class)
        ->call('restoreCategory', $category->id)
        ->assertSee('Kategori dan seluruh produk berhasil dipulihkan.');

    expect($category->fresh()->isActive)->toBeTrue()
        ->and($products->every(fn (Product $product): bool => $product->fresh()->isActive === true))->toBeTrue();
});

it('filters products by active status and search without leaking inactive sku matches', function () {
    $category = Category::factory()->create(['isActive' => true]);
    $active = Product::factory()->for($category)->create([
        'name' => 'Active Match',
        'sku' => 'MATCH-ACTIVE',
        'isActive' => true,
    ]);
    $inactive = Product::factory()->for($category)->create([
        'name' => 'Inactive Match',
        'sku' => 'MATCH-INACTIVE',
        'isActive' => false,
    ]);

    Livewire::test(ProductIndex::class)
        ->set('status', 'active')
        ->set('search', 'MATCH')
        ->assertSee($active->name)
        ->assertDontSee($inactive->name);
});

it('filters nonactive products', function () {
    $category = Category::factory()->create(['isActive' => true]);
    $active = Product::factory()->for($category)->create(['isActive' => true]);
    $inactive = Product::factory()->for($category)->create(['isActive' => false]);

    Livewire::test(ProductIndex::class)
        ->set('status', 'nonactive')
        ->assertSee($inactive->name)
        ->assertDontSee($active->name);
});
