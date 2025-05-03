<?php
session_start();

$users = [
    'admin' => 'admin123',
    'user' => 'password123'
];

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (isset($users[$username]) && $users[$username] === $password) {
            $_SESSION['username'] = $username;
            header('Location: easy_vulnerable.php?logged_in=1');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header('Location: easy_vulnerable.php');
        exit;
    }
}

$logged_in = isset($_SESSION['username']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; }
        h1 { color: #333; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Login</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logged_in']) && $_GET['logged_in'] == '1'): ?>
            <div class="success">You have successfully logged in!</div>
        <?php endif; ?>
        
        <?php if (!$logged_in): ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login">Login</button>
            </form>
        <?php else: ?>
            <div>
                <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>! You are logged in.</p>
                
                <form method="post" action="">
                    <button type="submit" name="logout">Logout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 