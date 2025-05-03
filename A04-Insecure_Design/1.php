<?php
session_start();

$users = [
    'admin@example.com' => [
        'user_id' => 1001,
        'name' => 'Admin User',
        'password' => '$2y$10$BItoeFmS5qCBhY9QXgbMauUKf/YOYjpEEuqK4o2wRVt7xqSEYZJaG',
        'account_number' => '8675309421',
        'balance' => 54280.75
    ],
    'john.smith@example.com' => [
        'user_id' => 1002,
        'name' => 'John Smith',
        'password' => '$2y$10$FuwVhwARjw94oyMJ1mN3EekuDlnB3Y4fAEUX3MOjrZckuQpw3kr/y',
        'account_number' => '1234567890',
        'balance' => 12450.32
    ],
    'maria.garcia@example.com' => [
        'user_id' => 1003,
        'name' => 'Maria Garcia',
        'password' => '$2y$10$tHN7vIJtSqMhKE74i7o.deQgnhL5WQkxtcVm8steUQElbcTgRqoXG',
        'account_number' => '9876543210', 
        'balance' => 75689.91
    ]
];

$reset_tokens = [];
$error = '';
$success = '';
$currentStep = isset($_GET['step']) ? $_GET['step'] : 'request';

function logAction($action, $details) {
    file_put_contents('reset_log.txt', date('Y-m-d H:i:s') . " - $action - $details\n", FILE_APPEND);
}

function generateResetToken($userId) {
    $randomBytes = random_bytes(16);
    $randomHex = bin2hex($randomBytes);
    
    $timestamp = time();
    $encodedUserId = $userId ^ 0x1A3B5C7D;
    
    $tokenParts = [
        substr(dechex($encodedUserId), -8),
        substr(dechex($timestamp), -8),
        $randomHex
    ];
    
    $token = implode('-', $tokenParts);
    
    return $token;
}

function validateResetToken($token, $email = null) {
    global $reset_tokens, $users;
    
    if (!isset($reset_tokens[$token])) {
        return false;
    }
    
    if ($email !== null && $reset_tokens[$token]['email'] !== $email) {
        return false;
    }
    
    $tokenTime = $reset_tokens[$token]['created_at'];
    if (time() - $tokenTime > 48 * 3600) {
        unset($reset_tokens[$token]);
        return false;
    }
    
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_reset'])) {
        $email = $_POST['email'];
        
        if (!isset($users[$email])) {
            $error = "Email not found in our system.";
        } else {
            $userId = $users[$email]['user_id'];
            $token = generateResetToken($userId);
            
            $reset_tokens[$token] = [
                'email' => $email,
                'user_id' => $userId,
                'created_at' => time(),
                'used' => false
            ];
            
            $resetLink = "https://securebank.example.com/reset.php?token=$token&email=" . urlencode($email);
            $success = "A password reset link has been sent to $email.";
            
            $success .= "<br><br>Demo: <a href='?step=reset&token=$token&email=" . urlencode($email) . "'>$resetLink</a>";
            
            logAction("RESET_REQUESTED", "Email: $email, Token: $token");
        }
    } elseif (isset($_POST['reset_password'])) {
        $token = $_POST['token'];
        $email = $_POST['email'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
        } 
        elseif (!isset($reset_tokens[$token])) {
            $error = "Invalid or expired token.";
        } 
        else {
            $tokenInfo = $reset_tokens[$token];
            
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $users[$email]['password'] = $hashedPassword;
            
            $reset_tokens[$token]['used'] = true;
            
            $success = "Password has been reset successfully! You can now log in with your new password.";
            logAction("PASSWORD_RESET", "Email: $email, Token: $token");
            
            $currentStep = 'success';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    if (isset($reset_tokens[$token])) {
        $currentStep = 'reset';
    } else {
        $error = "Invalid or expired reset token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureBank - Account Recovery</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
            background-color: #f9f9f9;
        }
        .header {
            text-align: center;
            padding: 20px;
            background-color: #00529B;
            color: white;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 20px;
            font-weight: normal;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .form-container {
            background-color: white;
            border: 1px solid #ddd;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .error {
            color: #d9534f;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 3px;
        }
        .success {
            color: #5cb85c;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #dff0d8;
            border-radius: 3px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        input[type="submit"] {
            background-color: #00529B;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #003d74;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #777;
            font-size: 14px;
        }
        .security-note {
            background-color: #f8f9fa;
            padding: 10px;
            margin-top: 20px;
            border-left: 4px solid #00529B;
            font-size: 14px;
        }
        a {
            color: #00529B;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">SecureBank</div>
        <h1>Account Recovery Portal</h1>
        <h2>Reset Your Password</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <div class="step <?php echo $currentStep === 'request' ? 'active' : ''; ?>" id="request-step">
            <h3>Request Password Reset</h3>
            <p>Please enter your email address to receive a password reset link.</p>
            
            <form method="post" action="?step=request">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required autocomplete="email">
                
                <input type="submit" name="request_reset" value="Send Reset Link">
            </form>
            
            <div class="security-note">
                <strong>Security Note:</strong> For your protection, a reset link will be sent to your registered email address. The link will expire after 48 hours.
            </div>
            
            <p><a href="login.php">Return to Login</a></p>
        </div>
        
        <div class="step <?php echo $currentStep === 'reset' ? 'active' : ''; ?>" id="reset-step">
            <h3>Create New Password</h3>
            <p>Please enter and confirm your new password below.</p>
            
            <form method="post" action="?step=reset">
                <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
                <input type="hidden" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                
                <input type="submit" name="reset_password" value="Reset Password">
            </form>
            
            <div class="security-note">
                <strong>Password Requirements:</strong> Your password must be at least 8 characters long and include a mix of letters, numbers, and special characters.
            </div>
        </div>
        
        <div class="step <?php echo $currentStep === 'success' ? 'active' : ''; ?>" id="success-step">
            <h3>Password Reset Complete</h3>
            <p>Your password has been successfully reset.</p>
            <p>For security reasons, you should log in with your new password right away.</p>
            <p><a href="login.php" class="btn">Go to Login</a></p>
            
            <div class="security-note">
                <strong>Security Tip:</strong> Remember to use a unique password for each of your online accounts. Never share your password with anyone, including SecureBank staff.
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2025 SecureBank Financial Services. All rights reserved.</p>
        <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a> | <a href="#">Security</a></p>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentStep = "<?php echo $currentStep; ?>";
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
            });
            const activeStep = document.getElementById(currentStep + '-step');
            if (activeStep) {
                activeStep.classList.add('active');
            }
        });
    </script>
</body>
</html>