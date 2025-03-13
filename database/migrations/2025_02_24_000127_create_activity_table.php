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
        Schema::create('activity', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('activity_name');
            $table->string('module');
            $table->string('session');
            $table->text('activity_description');
            $table->string('speaker_name');
            $table->string('TBI');
            $table->date('due_date');
            $table->string('activityFile_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity');
    }
};
