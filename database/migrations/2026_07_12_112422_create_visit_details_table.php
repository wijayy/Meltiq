<?php

use App\Models\Product;
use App\Models\Visit;
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
        Schema::create('visit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Visit::class)->constrained('visits');
            $table->foreignIdFor(Product::class)->constrained('products');
            $table->integer('stockBefore');
            $table->integer('physicalStock');
            $table->integer('returnedQty');
            $table->integer('expiredQty');
            $table->integer('newDeliveryQty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_details');
    }
};
