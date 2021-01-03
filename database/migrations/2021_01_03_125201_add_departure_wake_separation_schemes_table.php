<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDepartureWakeSeparationSchemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('departure_wake_separation_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Key for the scheme');
        });

        DB::table('departure_wake_separation_schemes')
            ->insert(
                [
                    [
                        'key' => 'UK',
                    ],
                    [
                        'key' => 'RECAT_EU',
                    ],
                ]
            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('departure_wake_separation_schemes');
    }
}
