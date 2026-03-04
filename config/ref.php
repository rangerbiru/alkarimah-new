<?php
return [
    'bank' => [
        'bsi' => [
            'number' => env('BANK_BSI'),
            'name' => env('BANK_BSI_NAME'),
        ],
        'bni' => [
            'number' => env('BANK_BNI'),
            'name' => env('BANK_BNI_NAME'),
        ]
    ],
    'moota' => [
        'user' => env('MOOTA_USER_ID'),
        'credential' => [
            'bsi' => [
                'id' => env('MOOTA_BSI_ID'),
                'secret' => env('MOOTA_BSI_SECRET')
            ],
            'bni' => [
                'id' => env('MOOTA_BNI_ID'),
                'secret' => env('MOOTA_BNI_SECRET')
            ],
        ]
    ]
];
