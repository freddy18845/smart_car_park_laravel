<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parking_space_id'); // parent parking space
            $table->string('name'); // e.g. "Spot A"
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('directions')->nullable(); // ✅ New field
            $table->decimal('distance', 8, 2)->nullable(); // in meters
            $table->timestamps();

            $table->foreign('parking_space_id')
                ->references('id')->on('parking_spaces')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
    }
};
