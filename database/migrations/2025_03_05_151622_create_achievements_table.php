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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('competition_name');
            $table->string('organized_by');
            $table->date('date_achieved');
            $table->decimal('prize_amount', 10, 2)->unsigned();
            $table->unsignedBigInteger('startup_profile_id');
            $table->json('photos')->nullable();
            $table->string('category')->default('Award');
            $table->string('description')->nullable();
            $table->string('event_location')->nullable();
            $table->string('article_link')->nullable();
            $table->timestamps();

            $table->foreign('startup_profile_id')->references('id')->on('startup_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
