# A02: Cryptographic Failures

## Description
Cryptographic Failures relate to failures in cryptography, or the complete absence of encryption when needed. These issues often lead to sensitive data exposure. Cryptographic failures can occur when:

- Data is transmitted in clear text (e.g., HTTP instead of HTTPS)
- Weak cryptographic algorithms or protocols are used
- Weak keys are generated or managed improperly
- Cryptographic functions are misused (e.g., improper mode of operation)
- Using deprecated or weak cryptographic functions (MD5, SHA1)
- Using hardcoded, default, or weak cryptographic keys

## Vulnerable Code Examples

### Easy Example: Using MD5 for Password Hashing

This code uses MD5 for password hashing, which is considered cryptographically broken:

```php
$users = [
    'admin' => md5('admin123'),
    'user' => md5('password123')
];

if (isset($users[$username]) && $users[$username] === md5($password)) {
    $message = "Login successful!";
} else {
    $message = "Invalid username or password";
}
```

### Hard Example: Using SHA256 with a Static Salt

This code uses SHA256 with a static salt, which is vulnerable to rainbow table attacks:

```php
public function authenticate($email, $password) {
    $email = $this->db->escape($email);
    $password_hash = hash('sha256', $password . 'salt1234');
    
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password_hash'";
    $result = $this->db->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Obtain the MD5 hash of a user's password (e.g., from a database breach)
2. Use a rainbow table or MD5 lookup service to find the original password
3. For example: MD5 hash `482c811da5d5b4bc6d497ffa98491e38` can be quickly reversed to `password123`
4. Log in to the user's account with the cracked password

### Hard Example PoC
1. Obtain the SHA256 hash of a user's password with the static salt
2. Since the salt is hardcoded (`salt1234`), an attacker can pre-compute hashes for common passwords
3. Create a custom rainbow table with the known salt
4. Crack password hashes obtained from a database breach

## Security Fix Recommendations

### For Easy Example:
```php
// Use password_hash() and password_verify() for secure password hashing
$users = [
    'admin' => password_hash('admin123', PASSWORD_DEFAULT),
    'user' => password_hash('password123', PASSWORD_DEFAULT)
];

if (isset($users[$username]) && password_verify($password, $users[$username])) {
    $message = "Login successful!";
} else {
    $message = "Invalid username or password";
}
```

### For Hard Example:
```php
public function authenticate($email, $password) {
    $email = $this->db->escape($email);
    
    // First, get the user by email only
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $this->db->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Use password_verify() to verify the hash
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    
    return null;
}

// When storing passwords
function registerUser($email, $password) {
    $email = $this->db->escape($email);
    // Use password_hash() which automatically generates and stores a secure salt
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (email, password) VALUES ('$email', '$passwordHash')";
    return $this->db->query($query);
}
``` 