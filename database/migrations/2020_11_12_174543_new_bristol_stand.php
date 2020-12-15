<?php

use App\Services\DependencyService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NewBristolStand extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('stands')
            ->insert(
                [
                    'airfield_id' => DB::table('airfield')->where('code', 'EGGD')->first()->id,
                    'identifier' => '7N',
                    'latitude' => 51.38617833,
                    'longitude' => -2.70836889,
                    'created_at' => Carbon::now(),
                ]
            );

        DependencyService::touchDependencyByKey('DEPENDENCY_STANDS');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('stands')
            ->where('identifier', '7N')
            ->where('airfield_id', DB::table('airfield')->where('code', 'EGGD')->first()->id)
                ->delete();

        DependencyService::touchDependencyByKey('DEPENDENCY_STANDS');
    }
}
