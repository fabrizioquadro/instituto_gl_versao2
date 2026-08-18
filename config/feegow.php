<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feegow API
    |--------------------------------------------------------------------------
    |
    | Credenciais da API Feegow (fonte dos pacientes). O token é um JWT
    | configurado no .env (FEEGOW_TOKEN) — nunca versionar no código.
    |
    */
    'base_url' => env('FEEGOW_BASE_URL', 'https://api.feegow.com/v1/api'),
    'token' => env('FEEGOW_TOKEN'),

    /*
    | Prefixo adicionado às obs/notas dos agendamentos criados na Feegow.
    | Útil para identificar envios de teste/implementação.
    */
    'obs_prefix' => env('FEEGOW_OBS_PREFIX', ''),
];
