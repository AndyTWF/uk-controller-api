<?php

return [
    'columns' => [
        'airfield' => 'Airfield',
        'description' => 'Description',
        'airlines' => 'Number of Assigned Airlines',
    ],
    'airlines' => [
        'description' => 'Airlines can be assigned to specific terminals based on various parameters. See the allocation guide
        for more details.',
        'columns' => [
            'icao' => 'ICAO Code',
            'destination' => 'Origin',
            'callsign' => 'Callsign',
            'callsign_slug' => 'Partial Callsign',
            'priority' => 'Allocation Priority',
        ],
    ],
];
