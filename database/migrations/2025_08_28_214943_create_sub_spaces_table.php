<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_spaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parking_spot_id'); // parent spot
            $table->string('label'); // e.g. "Zone A1"
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->timestamps();

            $table->foreign('parking_spot_id')
                ->references('id')->on('parking_spots')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_spaces');
    }
};
