<?php

return [
    'api_key' => env('XENDIT_API_KEY'),
    'callback_verification_token' => env('XENDIT_CALLBACK_VERIFICATION_TOKEN'), 
    'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
];