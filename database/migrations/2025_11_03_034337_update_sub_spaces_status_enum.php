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
        DB::statement("
            ALTER TABLE sub_spaces 
            MODIFY COLUMN status 
            ENUM('available', 'occupied', 'reserved', 'maintenance', 'booked') 
            DEFAULT 'available'
        ");
    }

    public function down()
    {
        DB::statement("
            ALTER TABLE sub_spaces 
            MODIFY COLUMN status 
            ENUM('available', 'occupied', 'reserved', 'maintenance') 
            DEFAULT 'available'
        ");
    }
};
