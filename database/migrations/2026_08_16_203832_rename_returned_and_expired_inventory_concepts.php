<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visit_details', function (Blueprint $table) {
            $table->unsignedInteger('discountQty')->default(0)->after('returnedQty');
            $table->unsignedInteger('damagedQty')->default(0)->after('expiredQty');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('movement_type', ['production', 'transfer', 'sale', 'return', 'expired', 'discount', 'damaged', 'adjustment'])->change();
        });

        DB::table('settings')->where('key', 'default_expired_location')->get()->each(function (object $setting): void {
            DB::table('settings')->updateOrInsert(
                ['key' => 'default_damaged_location'],
                ['value' => $setting->value, 'type' => $setting->type, 'created_at' => now(), 'updated_at' => now()],
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'default_damaged_location')->delete();

        Schema::table('visit_details', function (Blueprint $table) {
            $table->dropColumn(['discountQty', 'damagedQty']);
        });
    }
};
