<?php

namespace App\Rules\Controller;

use App\BaseUnitTestCase;
use Illuminate\Support\Facades\Validator;

class ControllerPositionFrequencyTest extends BaseUnitTestCase
{
    protected function validateResult(mixed $value)
    {
        return !Validator::make(
            [
                'frequency' => $value,
            ],
            [
                'frequency' => new ControllerPositionFrequency(),
            ]
        )
            ->fails();
    }

    /**
     * @dataProvider frequencyProvider
     */
    public function testItValidatesFrequency(mixed $frequency, bool $expected)
    {
        $this->assertEquals($expected, $this->validateResult($frequency));
    }

    public function frequencyProvider(): array
    {
        return [
            'Null' => [null, false],
            'Numeric' => [123, false],
            'Alpha string' => ['abc.def', false],
            'Short no decimal' => ['123', false],
            'Long no decimal' => ['123456', false],
            'Only one DP' => ['123.1', false],
            'Only two DP' => ['123.12', false],
            'Too long' => ['129.4200', false],
            'Too long two' => ['129.4205', false],
            'Old frequencies' => ['129.420', false],
            'Not 25KHz Spaced' => ['129.133', false],
            '25KHz Zero' => ['129.000', true],
            '25KHz 25' => ['129.025', true],
            '25KHz 50' => ['129.050', true],
            '25KHz 75' => ['129.075', true],
            '25KHz Zero Two' => ['118.400', true],
            '25KHz 25 Two' => ['118.425', true],
            '25KHz 50 Two' => ['118.450', true],
            '25KHz 75 Two' => ['118.475', true],
        ];
    }
}
