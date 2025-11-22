<?php
// includes/config.example.php
// نسخة مثال - انسخها إلى config.php وعدّل القيم

return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'YOUR_APP_PASSWORD',
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'منصة إبداع'
    ],
    'paths' => [
        'uploads' => __DIR__ . '/../uploads/',
        'certificates' => __DIR__ . '/../uploads/certificates/',
        'templates' => __DIR__ . '/../templates/'
    ],
    'app' => [
        'env' => 'production',
        'debug' => false,
        'url' => 'https://your-domain.com'
    ],
    'db' => [
        'host' => 'localhost',
        'name' => 'ibdaa_platform',
        'user' => 'root',
        'pass' => ''
    ]
];
