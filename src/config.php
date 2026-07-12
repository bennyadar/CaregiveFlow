<?php
// ==== CaregiveFlow Stage 1: Config ====
// Rename this file to config.php (already named) and set your DB credentials.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'caregiveflow',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // App settings
    'app' => [
        'debug' => true,
        'base_path' => '', // if your app isn't at the web root, put subfolder here (e.g., '/caregiveflow')
        'session_name' => 'cgf_session',
    ],
];
