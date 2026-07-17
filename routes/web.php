<?php

use App\Livewire\Dashboard;
use App\Livewire\LocationIndex;
use App\Livewire\ProductIndex;
use App\Livewire\ProductionCreate;
use App\Livewire\ProductionIndex;
use App\Livewire\ProductionShow;
use App\Livewire\StockIndex;
use App\Livewire\StockMovementIndex;
use App\Livewire\UserIndex;
use App\Livewire\VisitCreate;
use App\Livewire\VisitIndex;
use App\Livewire\VisitShow;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('products', ProductIndex::class)->name('products.index');
    Route::get('locations', LocationIndex::class)->name('locations.index');
    Route::get('stocks', StockIndex::class)->name('stocks.index');
    Route::get('stock-movements', StockMovementIndex::class)->name('stock-movements.index');
    Route::get('productions', ProductionIndex::class)->name('productions.index');
    Route::get('productions/create', ProductionCreate::class)->name('productions.create');
    Route::get('productions/{production:slug}', ProductionShow::class)->name('productions.show');
    Route::get('productions/{production:slug}/edit', ProductionCreate::class)->name('productions.edit');
    Route::get('visits', VisitIndex::class)->name('visits.index');
    Route::get('visits/create', VisitCreate::class)->name('visits.create');
    Route::get('visits/{visit:slug}', VisitShow::class)->name('visits.show');
    Route::get('visits/{visit:slug}/edit', VisitCreate::class)->name('visits.edit');
    Route::get('users', UserIndex::class)->name('users.index');
});

require __DIR__.'/settings.php';
