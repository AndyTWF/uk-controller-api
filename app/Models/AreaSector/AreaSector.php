<?php

namespace App\Models\AreaSector;
use App\Models\Controller\ControllerPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AreaSector extends Model
{
    protected $fillable = [
        'description'
    ];

    public function controllerPositions(): BelongsToMany
    {
        return $this->belongsToMany(ControllerPosition::class)
            ->withPivot('priority')
            ->withTimestamps();
    }
}
