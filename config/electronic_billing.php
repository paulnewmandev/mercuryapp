<?php

return [
    'sri' => [
        'reception' => [
            'development' => env('SRI_RECEPTION_URL_DEVELOPMENT', 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl'),
            'production' => env('SRI_RECEPTION_URL_PRODUCTION', 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl'),
        ],
        'authorization' => [
            'development' => env('SRI_AUTHORIZATION_URL_DEVELOPMENT', 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl'),
            'production' => env('SRI_AUTHORIZATION_URL_PRODUCTION', 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl'),
        ],
    ],
    'certificates' => [
        'path' => env('ELECTRONIC_BILLING_CERTIFICATES_PATH', 'storage/app/companies'),
    ],
    'pdf' => [
        'path' => 'storage/app/invoices',
    ],
];

