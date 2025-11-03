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
    Schema::table('sub_spaces', function (Blueprint $table) {
        $table->unsignedBigInteger('current_user_id')->nullable()->after('status');
        $table->foreign('current_user_id')->references('id')->on('users')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('sub_spaces', function (Blueprint $table) {
        $table->dropForeign(['current_user_id']);
        $table->dropColumn('current_user_id');
    });
}

};
