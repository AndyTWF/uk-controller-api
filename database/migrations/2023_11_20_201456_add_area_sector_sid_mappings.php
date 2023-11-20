<?php

use App\Models\Airfield\Airfield;
use App\Models\AreaSector\AreaSector;
use App\Models\Sid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    const SECTOR_SID_MAP = [
        'LTC_SW' => [
            'EGLL' => [
                'MAXIT',
                'MODMI',
                'GOGSI',
                'GASGU',
                'CPT',
                'CHK',
            ],
            'EGKK' => [
                'BOGNA',
                'HARDY',
                'IMVUR',
                'NOVMA',
                'SFD',
            ],
            'EGLF' => [
                'GWC',
                'HAZEL'
            ],
        ],
        'LTC_SE' => [
            'EGLL' => [
                'DET',
            ],
            'EGLC' => [
                'SOQQA',
            ],
            'EGKK' => [
                'FRANE',
                'MIMFO',
                'ODVIK',
                'LAM'
            ],
            'EGKB' => [
                'DAGGA',
                'BPK',
                'DVR',
                'LYD',
            ],
            'EGMC' => [
                'DVR',
                'LYD',
            ],
        ],
        'LTC_NW' => [
            'EGSS' => [
                'NUGBO',
                'UTAVA',
            ],
            'EGGW' => [
                'OLNEY',
                'RODNI',
            ],
            'EGLC' => [
                'BPK',
                'SAXBI',
            ],
            'EGLL' => [
                'UMLAT',
                'ULTIB',
            ],
            'EGMC' => [
                'BPK',
                'CPT'
            ]
        ],
        'LTC_NE' => [
            'EGSS' => [
                'CLN',
                'DET',
                'LAM',
            ],
            'EGGW' => [
                'DET',
                'MATCH',
            ],
            'EGLC' => [
                'BPK',
                'ODUKU',
                'SAXBI',
            ],
            'EGLL' => [
                'BPK'
            ],
            'EGMC' => [
                'BPK',
                'CPT',
                'CLN',
            ]
        ],
        'LTC_E' => [
            'EGSS' => [
                'CLN',
                'DVR',
            ],
            'EGGW' => [
                'MATCH',
                'DVR',
            ],
            'EGLC' => [
                'BPK',
            ],
            'EGLL' => [
                'BPK',
            ],
            'EGKK' => [
                'FRANE',
                'DAGGA',
            ],
            'EGKB' => [
                'FRANE',
                'DAGGA',
            ],
            'EGMC' => [
                'CLN',
            ]
        ]
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::SECTOR_SID_MAP as $sector => $airfields) {
                $sector = AreaSector::where('description', $sector)->firstOrFail();
                
                foreach($airfields as $airfield => $sids) {
                    foreach ($sids as $sid) {
                        $sidIds = Sid::whereHas('runway.airfield', function ($query) use ($airfield) {
                                $query->where('code', $airfield);
                            })
                            ->where('identifier', 'like', $sid . '%')
                            ->pluck('id');

                        $sector->interestedSids()->attach($sidIds);
                    }
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
