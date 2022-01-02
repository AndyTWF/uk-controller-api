<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTimeColumnsOnStandReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stand_reservations', function (Blueprint $table) {
            $table->timestamp('reserved_at')
                ->after('callsign')
                ->index()
                ->comment('The time at which the stand is reserved');
            $table->dropColumn('start');
            $table->dropColumn('end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stand_reservations', function (Blueprint $table) {
            $table->dropColumn('reserved_at');
            $table->timestamp('start')->index()->comment('The time the reservation starts');
            $table->timestamp('end')->index()->comment('The time the reservation ends');
        });
    }
}
