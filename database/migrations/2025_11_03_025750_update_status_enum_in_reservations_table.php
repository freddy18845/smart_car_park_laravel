<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('booked', 'reserved', 'occupied', 'completed', 'cancelled') NOT NULL DEFAULT 'booked'");
}

public function down()
{
    DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('reserved', 'occupied', 'completed', 'cancelled') NOT NULL DEFAULT 'reserved'");
}
};
