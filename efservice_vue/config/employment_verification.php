<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Employment Verification Settings
    |--------------------------------------------------------------------------
    */

    // Number of days before a verification token expires
    'token_expiry_days' => env('EMPLOYMENT_VERIFICATION_TOKEN_EXPIRY_DAYS', 30),

    // Maximum number of verification email attempts per employment record
    'max_attempts' => env('EMPLOYMENT_VERIFICATION_MAX_ATTEMPTS', 3),
];
