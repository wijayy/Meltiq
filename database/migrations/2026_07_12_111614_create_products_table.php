<?php

use App\Models\Category;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->foreignIdFor(Category::class)->constrained()->cascadeOnDelete();
            $table->string("sku")->unique();
            $table->string("slug");
            $table->text("description");
            $table->integer("transferPrice");
            $table->integer("salePrice");
            $table->integer("costPrice");
            $table->boolean("isActive")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};