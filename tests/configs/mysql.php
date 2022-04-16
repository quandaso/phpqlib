<?php
$dbConfig = env('DB');
return [
    'default' => [
        'host' => $dbConfig['host'],
        'port' => $dbConfig['port'],
        'database' => $dbConfig['name'],
        'username' => $dbConfig['username'],
        'password' => $dbConfig['password']
    ],
    'test' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'test',
        'username' => 'root',
        'password' => 'quantm'
    ]
];