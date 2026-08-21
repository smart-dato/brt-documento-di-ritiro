<?php

return [
    'max_parcels' => 20,

    'allocator' => [
        'connection' => null,
        'table' => 'brt_documento_di_ritiro',
    ],

    'fallback_delivery_address' => [
        'company_name' => 'OMEST SRL',
        'street' => 'Via L. Negrelli Straße 15',
        'zipcode' => '39100',
        'city' => 'BOLZANO',
        'country_code' => 'it',
        'country_name' => 'Italy',
        'email' => 'info@europacco.com',
        'phone' => '+39 0471 1962318',
    ],

    'fonts' => [
        'franklin_gothic' => null,
        'verdana' => null,
        'verdana_bold' => null,
        'verdana_italic' => null,
        'verdana_bold_italic' => null,
        'verdana_mono' => null,
        'verdana_ref' => null,
    ],

    'images' => [
        'brt_logo' => null,
        'brt_logo_text' => null,
        'brt_barcode_background' => null,
    ],
];
