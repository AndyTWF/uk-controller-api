<?php

namespace App\Models\Airline;

use App\Models\Airfield\Terminal;
use App\Models\Stand\Stand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Airline extends Model
{
    protected $fillable = [
        'icao_code',
        'name',
        'callsign',
        'is_cargo',
    ];

    protected $casts = [
        'is_cargo' => 'boolean',
    ];

    public function terminals(): BelongsToMany
    {
        return $this->belongsToMany(Terminal::class)
            ->withPivot('id', 'destination', 'priority', 'callsign_slug', 'callsign', 'aircraft_id')
            ->withTimestamps();
    }

    public function stands(): BelongsToMany
    {
        return $this->belongsToMany(Stand::class)
            ->withPivot('id', 'destination', 'priority', 'not_before', 'callsign_slug', 'callsign', 'aircraft_id')
            ->withTimestamps();
    }
}
