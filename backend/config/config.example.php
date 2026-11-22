<?php
// Copy this file to config.php and fill in your own values.
// config.php is excluded from Git and must never be committed.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'forkcast',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => 'CHANGE_ME',          // used from Milestone 4
        'expires_in' => 86400,
    ],
    'mealdb' => [
        'base_url' => 'https://www.themealdb.com/api/json/v1/',
        'api_key' => '1',                 // used from Milestone 3
        'timeout' => 5,
    ],
];