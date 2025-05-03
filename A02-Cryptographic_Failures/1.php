<?php
$users = [
    'admin' => md5('admin123'),
    'user' => md5('password123')
];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (isset($users[$username]) && $users[$username] === md5($password)) {
        $message = "Login successful!";
    } else {
        $message = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login System</title>
</head>
<body>
    <h1>Login System</h1>
    
    <?php if ($message): ?>
        <div><?php echo $message; ?></div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
</body>
</html> 