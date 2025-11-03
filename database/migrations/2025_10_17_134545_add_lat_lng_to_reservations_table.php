<?php

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
        Schema::table('reservations', function (Blueprint $table) {
            // Add GPS coordinates for tracking user proximity
            $table->decimal('latitude', 10, 7)->nullable()->after('end_time');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
   {
        Schema::table('reservations', function (Blueprint $table) {
            // Remove GPS coordinate columns if migration is rolled back
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
