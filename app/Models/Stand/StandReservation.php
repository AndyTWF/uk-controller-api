<?php

namespace App\Models\Stand;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StandReservation extends Model
{
    protected $fillable = [
        'stand_id',
        'callsign',
        'reserved_at',
        'origin',
        'destination'
    ];

    protected $dates = [
        'start',
        'end',
    ];

    protected $casts = [
        'stand_id' => 'integer',
    ];

    public function stand(): BelongsTo
    {
        return $this->belongsTo(Stand::class);
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $this->scopeReservedAtBetween(
            $builder,
            Carbon::now()->subMinutes(30),
            Carbon::now()->addMinutes(30)
        );
    }

    public function scopeUpcoming(Builder $builder, Carbon $before): Builder
    {
        return $builder->where('reserved_at', '>', Carbon::now())
            ->where('reserved_at', '<=', $before);
    }

    public function scopeReservedAtBetween(Builder $builder, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $builder->whereBetween('reserved_at', [$start, $end]);
    }

    public function scopeStandId(Builder $builder, int $standId): Builder
    {
        return $builder->where('stand_id', $standId);
    }

    public function scopeCallsign(Builder $builder, string $callsign): Builder
    {
        return $builder->where('callsign', $callsign);
    }
}
