<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resource Limits
    |--------------------------------------------------------------------------
    |
    | These values control resource limits for users. A value of -1 means
    | unlimited.
    |
    */

    'limits' => [
        'destinations_per_user' => (int) env('ISIR_MAX_DESTINATIONS_PER_USER', -1),
        'digests_per_user' => (int) env('ISIR_MAX_DIGESTS_PER_USER', -1),
        'github_repos_per_digest' => (int) env('ISIR_MAX_GITHUB_REPOS_PER_DIGEST', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Timezones
    |--------------------------------------------------------------------------
    |
    | A curated list of common timezones for digest scheduling.
    |
    */

    'timezones' => [
        'UTC',
        'America/New_York',
        'America/Chicago',
        'America/Denver',
        'America/Los_Angeles',
        'America/Toronto',
        'America/Vancouver',
        'America/Sao_Paulo',
        'Europe/London',
        'Europe/Paris',
        'Europe/Berlin',
        'Europe/Amsterdam',
        'Europe/Madrid',
        'Europe/Rome',
        'Asia/Dubai',
        'Asia/Kolkata',
        'Asia/Singapore',
        'Asia/Hong_Kong',
        'Asia/Tokyo',
        'Asia/Seoul',
        'Asia/Shanghai',
        'Australia/Sydney',
        'Australia/Melbourne',
        'Pacific/Auckland',
    ],

];
