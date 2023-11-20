<?php

use App\Models\AreaSector\AreaSector;
use App\Models\Controller\ControllerPosition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    const AREA_SECTORS = [
        'LTC_SW' => [
            'LTC_SW_CTR',
            'LTC_S_CTR',
            'LTC_CTR',
            'LON_S_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LTC_SE' => [
            'LTC_SE_CTR',
            'LTC_S_CTR',
            'LTC_CTR',
            'LON_D_CTR',
            'LON_S_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LTC_NW' => [
            'LTC_NW_CTR',
            'LTC_N_CTR',
            'LTC_CTR',
            'LON_M_CTR',
            'LON_C_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LTC_NE' => [
            'LTC_NE_CTR',
            'LTC_N_CTR',
            'LTC_CTR',
            'LTC_E_CTR',
            'LON_E_CTR',
            'LON_C_CTR',
            'LON_S_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LTC_E' => [
            'LTC_E_CTR',
            'LON_E_CTR',
            'LON_C_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'MAN_W' => [
            'MAN_W_CTR',
            'MAN_CTR',
            'LON_NW_CTR',
            'LON_N_CTR',
            'LON_CTR'
        ],
        'MAN_NE' => [
            'MAN_NE_CTR',
            'MAN_E_CTR',
            'MAN_CTR',
            'LON_NE_CTR',
            'LON_N_CTR',
            'LON_CTR',
        ],
        'MAN_SE' => [
            'MAN_SE_CTR',
            'MAN_E_CTR',
            'MAN_CTR',
            'LON_N_CTR',
            'LON_NW_CTR',
            'LON_CTR',
        ],
        'LON_W' => [
            'LON_W_CTR',
            'LON_CTR'
        ],
        'LON_S' => [
            'LON_S_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LON_D' => [
            'LON_D_CTR',
            'LON_S_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LON_M' => [
            'LON_M_CTR',
            'LON_C_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LON_C' => [
            'LON_C_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LON_E' => [
            'LON_E_CTR',
            'LON_C_CTR',
            'LON_SC_CTR',
            'LON_CTR'
        ],
        'LON_NW' => [
            'LON_NW_CTR',
            'LON_N_CTR',
            'LON_CTR'
        ],
        'LON_N' => [
            'LON_N_CTR',
            'LON_CTR'
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::AREA_SECTORS as $areaSector => $controllerPositions) {
                $sector = AreaSector::create([
                    'description' => $areaSector
                ]);
    
                $priority = 1;
    
                foreach ($controllerPositions as $controllerPosition) {
                    $position = ControllerPosition::where('callsign', $controllerPosition)->firstOrFail();
                    $sector->controllerPositions()->attach($position, [
                        'priority' => $priority
                    ]);
    
                    $priority++;
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
