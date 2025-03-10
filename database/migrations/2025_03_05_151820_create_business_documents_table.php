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
        Schema::create('business_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('startup_profile_id');
            $table->string('dti_registration')->nullable();
            $table->string('bir_registration')->nullable();
            $table->string('sec_registration')->nullable();
            $table->timestamps();

            $table->foreign('startup_profile_id')->references('id')->on('startup_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_documents');
    }
};
