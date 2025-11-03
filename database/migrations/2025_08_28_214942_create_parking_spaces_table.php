<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_spaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id'); // operator managing the space
            $table->string('name');
            $table->string('location');
            $table->string('phone')->nullable();
            $table->string('other_contact')->nullable();
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->timestamps();

            // foreign key
            $table->foreign('operator_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_spaces');
    }
};
