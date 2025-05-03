<?php
session_start();

$is_admin = false;
if (isset($_GET['admin']) && $_GET['admin'] == 'true') {
    $is_admin = true;
}

if ($is_admin) {
    $sensitive_data = "Credit Card Numbers: 1234-5678-9012-3456, 9876-5432-1098-7654";
} else {
    $sensitive_data = "You don't have access to sensitive data";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
</head>
<body>
    <h1>User Dashboard</h1>
    
    <div>
        <h3>Sensitive Information:</h3>
        <p><?php echo $sensitive_data; ?></p>
    </div>
    
    <div>
        <a href="easy_vulnerable.php">Regular User View</a>
        <a href="easy_vulnerable.php?admin=true">Admin View</a>
    </div>
</body>
</html> 