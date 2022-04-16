<?php
return [
    'debug' => env('APP')['debug'],
    'locale' => 'en',
    'defaultTimeZone' => env('APP')['timezone'],
    'admin_mail' => env('ADMIN_MAIL', 'admin@finexpress.com')
];