# A07: Identification and Authentication Failures

## Description
Identification and Authentication Failures occur when functions related to user identity, authentication, and session management are implemented incorrectly. These vulnerabilities can allow attackers to compromise passwords, keys, or session tokens, or exploit implementation flaws to assume other users' identities temporarily or permanently.

Common authentication failures include:
- Permitting automated attacks like credential stuffing
- Allowing brute force attacks
- Permitting weak or well-known passwords
- Using weak credential recovery processes
- Using plain text, encrypted, or weakly hashed passwords
- Missing or ineffective multi-factor authentication
- Exposing session identifiers in URLs
- Not properly invalidating session IDs after logout or inactivity
- Not properly rotating session IDs after successful login

## Vulnerable Code Examples

### Easy Example: Plaintext Password Storage and Weak Authentication

This code stores passwords in plaintext and has no protection against brute force attacks:

```php
$users = [
    'admin' => 'admin123',
    'user' => 'password123'
];

if (isset($users[$username]) && $users[$username] === $password) {
    $_SESSION['username'] = $username;
    header('Location: easy_vulnerable.php?logged_in=1');
    exit;
} else {
    $error = 'Invalid username or password';
}
```

### Hard Example: Insufficient Session Security and Account Enumeration

This code contains multiple authentication weaknesses:

```php
public function login($username, $password) {
    $user = $this->findUserByUsername($username);
    
    if (!$user) {
        return false; // Reveals that the username doesn't exist
    }
    
    if ($this->verifyPassword($password, $user['password'])) {
        $this->createSession($user['id'], $user['username'], $user['role']);
        $this->updateLastLogin($user['id']);
        return true;
    }
    
    return false; // No brute force protection
}

private function createSession($userId, $username, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['auth_time'] = time();
    // No session regeneration, no secure cookies, no idle timeout
}

public function resetPassword($email, $token, $newPassword) {
    // No password complexity validation
    // Doesn't invalidate all existing sessions
    // No notification to user
}
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Access the login page at `easy_vulnerable.php`
2. Since passwords are stored in plaintext, if an attacker gains access to the code or database, they immediately have all user credentials
3. Try to login with common credentials like `admin:admin123` or `admin:password` - since there's no rate limiting, the attacker can try many combinations
4. Use burp suite or a similar tool to perform an automated brute force attack against the login page

### Hard Example PoC
1. Try to login with a non-existent username, note the response time
2. Try to login with an existing username but wrong password, note the response time is different
3. This timing difference allows an attacker to enumerate valid usernames
4. Since there's no brute force protection, an attacker can try multiple passwords for valid usernames
5. Once logged in, the session doesn't expire after inactivity and isn't regenerated on privilege changes
6. Session can be hijacked if the attacker steals the session cookie, as there are no additional validation checks

## Security Fix Recommendations

### For Easy Example:
```php
// Hash passwords properly with bcrypt/Argon2
$users = [
    'admin' => password_hash('admin123', PASSWORD_DEFAULT),
    'user' => password_hash('password123', PASSWORD_DEFAULT)
];

// Track login attempts to prevent brute force
$loginAttempts = isset($_SESSION['login_attempts'][$username]) ? $_SESSION['login_attempts'][$username] : 0;

if ($loginAttempts >= 5) {
    $timeLeft = 300 - (time() - $_SESSION['login_locked_time'][$username]);
    if ($timeLeft > 0) {
        $error = "Too many failed attempts. Try again in {$timeLeft} seconds.";
        exit;
    } else {
        $_SESSION['login_attempts'][$username] = 0;
    }
}

if (isset($users[$username]) && password_verify($password, $users[$username])) {
    // Reset login attempts on success
    $_SESSION['login_attempts'][$username] = 0;
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    
    header('Location: dashboard.php');
    exit;
} else {
    // Increment login attempts on failure
    $_SESSION['login_attempts'][$username] = $loginAttempts + 1;
    
    if ($_SESSION['login_attempts'][$username] >= 5) {
        $_SESSION['login_locked_time'][$username] = time();
    }
    
    // Use a consistent error message that doesn't reveal if username exists
    $error = 'Invalid username or password';
}
```

### For Hard Example:
```php
public function login($username, $password) {
    // Add rate limiting/brute force protection
    if ($this->isRateLimited($username)) {
        $this->logFailedAttempt($username, 'rate_limited');
        return ['success' => false, 'message' => 'Too many attempts. Please try again later.'];
    }
    
    $user = $this->findUserByUsername($username);
    
    // Constant time password checking to prevent timing attacks
    if (!$user || !$this->verifyPassword($password, $user['password'])) {
        $this->incrementFailedAttempts($username);
        $this->logFailedAttempt($username, 'invalid_credentials');
        
        // Same error message whether username exists or not
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
    
    // Check if MFA is required
    if ($user['mfa_enabled'] && !$this->verifyMFA($user['id'], $_POST['mfa_code'] ?? null)) {
        return ['success' => false, 'message' => 'Invalid MFA code.', 'require_mfa' => true];
    }
    
    // Reset failed attempts on successful login
    $this->resetFailedAttempts($username);
    
    // Create secure session
    $this->createSession($user['id'], $user['username'], $user['role']);
    $this->updateLastLogin($user['id']);
    
    return ['success' => true];
}

private function createSession($userId, $username, $role) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['auth_time'] = time();
    $_SESSION['expires_at'] = time() + 3600; // 1 hour session timeout
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Set secure cookies
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Check session validity on each request
public function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['expires_at'])) {
        return false;
    }
    
    // Check session expiration
    if (time() > $_SESSION['expires_at']) {
        $this->logout();
        return false;
    }
    
    // Check for IP or user agent changes
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        $this->logout();
        return false;
    }
    
    // Extend session on activity
    $_SESSION['expires_at'] = time() + 3600;
    
    return true;
}
```

## Additional Security Measures

1. **Multi-Factor Authentication**: Implement MFA for sensitive operations or all logins
2. **Password Policy**: Enforce strong password requirements and check against compromised passwords
3. **Rate Limiting**: Implement rate limiting and account lockout policies
4. **Secure Session Management**: Use secure cookies and proper session handling
5. **Audit Logging**: Log all authentication events, especially failures
6. **Security Headers**: Implement headers like Strict-Transport-Security and X-Frame-Options 