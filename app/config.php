<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('FOURCUS_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('FOURCUS_DB_PORT') ?: '3306',
        'name' => getenv('FOURCUS_DB_NAME') ?: 'fourcus',
        'user' => getenv('FOURCUS_DB_USER') ?: 'fourcus',
        'pass' => getenv('FOURCUS_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => '4cus CMS',
        'base_url' => rtrim(getenv('FOURCUS_BASE_URL') ?: 'https://4cus.team', '/'),
        'upload_dir' => dirname(__DIR__) . '/uploads',
        'upload_url' => '/uploads',
        'session_name' => 'fourcus_admin',
    ],
];