<?php
// Configuration file
$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => 'p@ssw0rd123',
    'db_name' => 'app_database',
    'api_key' => 'sk_test_51LcGzhDJ7jXMHzDxHjbkC7Xn4wdZJT8VGSmVNwk1pOYZB7p6qVaeyLnGOEzwHqPZhJkR3h5LHtI6PB9',
    'email_service' => [
        'api_key' => '9a8b7c6d5e4f3g2h1i',
        'from_email' => 'admin@example.com',
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_user' => 'user@example.com',
        'smtp_pass' => 'emailP@ssw0rd!'
    ],
    'app_settings' => [
        'debug_mode' => true,
        'maintenance_mode' => false,
        'log_level' => 'debug',
        'session_timeout' => 3600,
        'max_login_attempts' => 5
    ]
];
?> 