<?php

return [
    'clickup' => [
        /*
         * The ClickUp API URL
        */
        'base_url' => env('CLICKUP_BASE_URL', 'https://api.clickup.com/api/v2'),

        /*
         * The ClickUp api token, you can get it by following the following documentation
         * https://developer.clickup.com/docs/authentication
        */
        'api_token' => env('CLICKUP_API_TOKEN'),

        /*
         * File from which the tasks will be obtained
        */
        'changelog' => env('CHANGELOG_FILE', 'changelog.md'),
    ],
];
