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
        Schema::create('startup_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('startup_name');
            $table->string('industry');
            $table->UnsignedBigInteger('leader_id')->constraint()->onDelete('cascade');;
            $table->date('date_registered_dti')->nullable();
            $table->date('date_registered_bir')->nullable();
            $table->string('startup_founded');
            $table->string('startup_description')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();

            $table->foreign('leader_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('startup_profiles');
    }
};
