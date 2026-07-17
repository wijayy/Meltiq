<?php

use App\Models\Location;
use App\Models\User;
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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_no');
            $table->string('visit_date');
            $table->string("slug")->unique();

            $table->text('notes')->nullable();
            $table->foreignIdFor(Location::class)->constrained('locations');
            $table->foreignIdFor(User::class, 'created_by')->constrained();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};