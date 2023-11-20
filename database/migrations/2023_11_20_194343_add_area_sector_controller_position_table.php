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
        Schema::create('area_sector_controller_position', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_sector_id')->constrained()->cascadeOnDelete();
            $table->foreignId('controller_position_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('priority');
            $table->timestamps();

            $table->unique(['area_sector_id', 'controller_position_id'], 'area_sector_controller_position_unique');
            $table->unique(['area_sector_id', 'priority'], 'area_sector_controller_position_priority_unique');
            $table->index('priority', 'area_sector_controller_position_priority_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_sector_controller_position');
    }
};
