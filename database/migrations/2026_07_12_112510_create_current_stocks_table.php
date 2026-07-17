<?php

use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('current_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class)->constrained('products');
            $table->foreignIdFor(Location::class)->constrained('locations');
            $table->integer('stock');
            $table->unique(['product_id', 'location_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_stocks');
    }
};
