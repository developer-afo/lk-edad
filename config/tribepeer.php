<?php

return [
    'api_url' => env('TRIBEPEER_API_URL', 'https://www.tribepeer.com/api'),
    'client_id' => env('TRIBEPEER_CLIENT_ID'),
    'client_secret' => env('TRIBEPEER_CLIENT_SECRET'),
    'publishable_key' => env('TRIBEPEER_PUBLISHABLE_KEY'),
    // Must match an allowed origin on the publishable key.
    'origin' => env('TRIBEPEER_ORIGIN'),
];
