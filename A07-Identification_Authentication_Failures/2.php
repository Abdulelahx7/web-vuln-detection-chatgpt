<?php
session_start();

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function createJWT($userId, $username, $role) {
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    $payload = [
        'user_id' => $userId,
        'username' => $username,
        'role' => $role,
        'exp' => time() + 3600
    ];
    
    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    
    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", getSecretKey(), true);
    $signatureEncoded = base64UrlEncode($signature);
    
    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

function verifyJWT($token) {
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $token);
    
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

function getSecretKey() {
    return 'super_secret_key_12345';
}

function login($username, $password) {
    if ($username === 'admin' && $password === 'password123') {
        return [
            'id' => 1,
            'username' => 'admin',
            'role' => 'admin'
        ];
    } else if ($username === 'user' && $password === 'user123') {
        return [
            'id' => 2,
            'username' => 'user',
            'role' => 'user'
        ];
    }
    
    return null;
}

$message = '';
$currentUser = null;

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $user = login($username, $password);
    
    if ($user) {
        $token = createJWT($user['id'], $user['username'], $user['role']);
        $_SESSION['token'] = $token;
        $message = "Logged in successfully!";
    } else {
        $message = "Invalid username or password!";
    }
}

if (isset($_GET['tampertoken'])) {
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $_SESSION['token']);
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    $payload['role'] = 'admin';
    $newPayloadEncoded = base64UrlEncode(json_encode($payload));
    
    $_SESSION['token'] = "$headerEncoded.$newPayloadEncoded.$signatureEncoded";
    $message = "Token tampered! Role changed to admin without updating signature!";
}

if (isset($_POST['logout'])) {
    unset($_SESSION['token']);
    $message = "Logged out successfully!";
}

if (isset($_SESSION['token'])) {
    $currentUser = verifyJWT($_SESSION['token']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>JWT Authentication</title>
</head>
<body>
    <h1>JWT Authentication</h1>
    
    <?php if ($message): ?>
    <div style="color: <?php echo strpos($message, 'Invalid') === 0 ? 'red' : 'green'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!$currentUser): ?>
    <form method="POST">
        <div>
            <label>Username: <input type="text" name="username" required></label>
        </div>
        <div>
            <label>Password: <input type="password" name="password" required></label>
        </div>
        <div>
            <input type="submit" name="login" value="Log In">
        </div>
    </form>
    <?php else: ?>
    <div>
        <h2>Welcome, <?php echo $currentUser['username']; ?>!</h2>
        <p>Your role is: <strong><?php echo $currentUser['role']; ?></strong></p>
        
        <?php if ($currentUser['role'] === 'admin'): ?>
        <div style="background-color: #ffe0e0; padding: 10px; border: 1px solid #ff0000;">
            <h3>Admin Panel</h3>
            <p>This content should only be visible to admins.</p>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="submit" name="logout" value="Log Out">
        </form>
    </div>
    
    <div>
        <h3>Your JWT Token:</h3>
        <textarea rows="5" cols="80" readonly><?php echo $_SESSION['token']; ?></textarea>
    </div>
    
    <div>
        <h3>Decoded Token Payload:</h3>
        <pre><?php print_r($currentUser); ?></pre>
    </div>

    <?php endif; ?>
    
 
</body>
</html>