<?php

namespace App\Caster;

use InvalidArgumentException;
use App\Rules\UnitDiscreteSquawkRange\FlightRules;
use App\Rules\UnitDiscreteSquawkRange\UnitType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Validation\Rule;

class UnitDiscreteSquawkRangeRuleCaster implements CastsAttributes
{

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return Rule
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $rule = json_decode($value, true);
        switch ($rule['type']) {
            case 'UNIT_TYPE':
                return new UnitType($rule['rule']);
            case 'FLIGHT_RULES':
                return new FlightRules($rule['rule']);
        }

        throw new InvalidArgumentException('Invalid rule type');
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return json_encode($value);
    }
}
