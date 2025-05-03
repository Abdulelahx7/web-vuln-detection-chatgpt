<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => 'p@ssw0rd123',
    'db_name' => 'app_database',
    'api_key' => 'sk_test_51LcGzhDJ7jXMHzDxHjbkC7Xn4wdZJT8VGSmVNwk1pOYZB7p6qVaeyLnGOEzwHqPZhJkR3h5LHtI6PB9',
    'admin_email' => 'admin@example.com',
    'debug_mode' => true
];

function connectToDatabase() {
    global $config;
    
    try {
        $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        if ($config['debug_mode']) {
            echo "Error: " . $e->getMessage();
        } else {
            echo "An error occurred while connecting to database.";
        }
        return null;
    }
}

function getSystemInfo() {
    $info = [
        'PHP Version' => phpversion(),
        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'Server Protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
        'Server Port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
        'Request Method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'Request Time' => $_SERVER['REQUEST_TIME'] ?? 'Unknown',
        'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'Remote Address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'Remote Port' => $_SERVER['REMOTE_PORT'] ?? 'Unknown'
    ];
    
    return $info;
}

$system_info = getSystemInfo();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; }
        .panel-header { background-color: #f5f5f5; padding: 10px; margin: -15px -15px 15px; border-bottom: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="panel">
            <div class="panel-header">
                <h2>System Configuration</h2>
            </div>
            
            <table>
                <tr>
                    <th>Configuration</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($config as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>PHP Info</h2>
            </div>
            
            <?php phpinfo(); ?>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>System Information</h2>
            </div>
            
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($system_info as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html> 