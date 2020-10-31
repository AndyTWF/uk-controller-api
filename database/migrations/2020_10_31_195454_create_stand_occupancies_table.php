<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStandOccupanciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stand_occupancies', function (Blueprint $table){
            $table->id();
            $table->string('callsign')->comment('The callsign occupying the stand');
            $table->unsignedBigInteger('stand_id')->comment('The stand being occupied');
            $table->timestamps();

            $table->foreign('callsign')->references('callsign')->on('network_aircraft')->cascadeOnDelete();
            $table->foreign('stand_id')->references('id')->on('stands')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stand_occupancies');
    }
}
