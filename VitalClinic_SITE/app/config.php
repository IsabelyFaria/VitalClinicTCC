<?php

return [
    'app_name' => 'Vital Clinic',
    'timezone' => 'America/Sao_Paulo',
    'data' => [
        // demo: o site usa data/demo-state.json e funciona sem banco local.
        // api: as operações deverão ser encaminhadas para a API central.
        'mode' => getenv('VCTCC_DATA_MODE') ?: 'demo',
        'api_base_url' => rtrim(getenv('VCTCC_API_URL') ?: '', '/'),
        'api_token' => getenv('VCTCC_API_TOKEN') ?: '',
        'timeout' => (int) (getenv('VCTCC_API_TIMEOUT') ?: 8),
    ],
    'brand' => [
        'logo' => 'assets/brand/vital-clinic-logo.svg',
        'mark' => 'assets/brand/vital-clinic-mark.svg',
    ],
    'rules' => [
        'cancel_before_hours' => 24,
        'reschedule_before_hours' => 24,
        'booking_max_days' => 60,
    ],
];
