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
        Schema::create('area_sector_sid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_sector_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sid_id');
            $table->timestamps();

            $table->foreign('sid_id')->references('id')->on('sid')->cascadeOnDelete();
            $table->unique(['area_sector_id', 'sid_id'], 'area_sector_sid_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_sector_sid');
    }
};
