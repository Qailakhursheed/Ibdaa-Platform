<?php
// includes/config.php

return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'ha717781053@gmail.com', // Replace with actual email
        'password' => 'YOUR_APP_PASSWORD',     // Replace with actual app password
        'from_email' => 'ha717781053@gmail.com',
        'from_name' => 'منصة إبداع للتدريب والتأهيل'
    ],
    'paths' => [
        'uploads' => __DIR__ . '/../uploads/',
        'certificates' => __DIR__ . '/../uploads/certificates/',
        'templates' => __DIR__ . '/../templates/'
    ]
];
