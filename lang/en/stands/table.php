<?php

return [
    'columns' => [
        'airfield' => 'Airfield',
        'terminal' => 'Terminal',
        'identifier' => 'Identifier',
        'aerodrome_reference_code' => 'Aerodrome Reference Code',
        'max_size_wingspan' => 'Max Aircraft (Wingspan)',
        'max_size_length' => 'Max Aircraft (Length)',
        'airlines' => 'Airlines',
        'used' => 'Used',
        'priority' => 'Allocation Priority',
    ],
    'airlines' => [
        'description' => 'Airlines can be assigned to specific stands based on various parameters. See the allocation guide
        for more details.',
        'columns' => [
            'icao' => 'ICAO Code',
            'aircraft' => 'Aircraft Type',
            'destination' => 'Origin',
            'callsign' => 'Callsign',
            'callsign_slug' => 'Callsign Slug',
            'priority' => 'Allocation Priority',
            'not_before' => 'Not Before [UTC]',
        ],
    ],
    'paired' => [
        'description' => 'Stands that are paired cannot be simultaneously assigned to aircraft. ' .
            'Note, this does not prevent aircraft from spawning up on a stand!',
        'columns' => [
            'id' => 'Id',
            'airfield' => 'Airfield',
            'identifier' => 'Identifier',
        ],
    ],
];
