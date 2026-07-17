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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->dateTime('movement_date');
            $table->enum('movement_type', ['production', 'transfer', 'sale', 'return', 'expired', 'adjustment']);
            $table->foreignIdFor(Product::class)->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->foreignIdFor(Location::class, 'from_location_id')->nullable()->constrained('locations');
            $table->foreignIdFor(Location::class, 'to_location_id')->nullable()->constrained('locations');
            $table->string('reference_no')->nullable();
            $table->nullableMorphs('reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
