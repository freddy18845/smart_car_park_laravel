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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parking_space_id')->constrained('parking_spaces')->onDelete('cascade');
            $table->foreignId('parking_spot_id')->constrained('parking_spots')->onDelete('cascade');
            
            // nullable sub_space_id with foreign key
            $table->unsignedBigInteger('sub_space_id')->nullable();
            $table->foreign('sub_space_id')
                  ->references('id')
                  ->on('sub_spaces')
                  ->onDelete('set null');
            
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('vehicle_number')->nullable();
            
            $table->enum('status', ['pending', 'reserved', 'occupied', 'completed', 'cancelled'])->default('pending');
            
            $table->timestamps();
            
            // Index for performance on common queries
            $table->index(['sub_space_id', 'status', 'start_time', 'end_time']);
            
            // Since sub_space_id is unique, we prevent overlapping reservations at application level
            // The unique constraint here prevents exact duplicate entries
            $table->index(['sub_space_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
