# A09: Security Logging and Monitoring Failures

## Description
Security Logging and Monitoring Failures occur when applications do not adequately log, monitor, or alert on suspicious activities. Without proper logging and monitoring, breaches cannot be detected, and attackers can persist in systems undetected for extended periods. This category covers inadequate logging, detection, monitoring, and active response to security incidents.

Common security logging and monitoring failures include:
- Auditable events such as logins, failed logins, and high-value transactions not being logged
- Logs not being generated for the appropriate detail level or in a format that is difficult to process
- Logs not being monitored for suspicious activity in real-time or regularly
- Alerts and error messages revealing sensitive information to users
- Penetration testing and scans not triggering alerts
- Insufficient alerting thresholds and escalation processes

## Vulnerable Code Examples

### Easy Example: Missing Critical Logging

This code handles authentication but doesn't log important security events:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (isset($users[$username]) && $users[$username] === $password) {
            $_SESSION['username'] = $username;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}

function log_activity($message) {
    // This function is never used - no logging implemented
    $log_file = 'access.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
```

Also, in the dashboard.php file, critical operations lack logging:

```php
function delete_user($userId) {
    // In a real application, this would delete a user from the database
    // No logging of this critical action!
    return true;
}

function reset_password($userId) {
    // In a real application, this would reset a user's password
    // No logging of this critical action!
    return true;
}
```

### Hard Example: Insufficient Logging and Monitoring

This more complex example shows a system with inadequate logging:

```php
class UserManager {
    private $db;
    private $logger;
    
    public function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function updateProfile($userId, $data) {
        $changes = [];
        
        foreach ($data as $field => $value) {
            $field = $this->db->escape($field);
            $value = $this->db->escape($value);
            $changes[] = "$field = '$value'";
        }
        
        if (empty($changes)) {
            return false;
        }
        
        $changesStr = implode(', ', $changes);
        $sql = "UPDATE users SET $changesStr WHERE id = $userId";
        $result = $this->db->query($sql);
        
        // No logging of what fields were changed or by whom
        
        return $result;
    }
    
    public function changePassword($userId, $newPassword) {
        $userId = intval($userId);
        $newPassword = $this->db->escape($newPassword);
        
        $sql = "UPDATE users SET password = '$newPassword' WHERE id = $userId";
        $result = $this->db->query($sql);
        
        if ($result) {
            // Only basic logging, no details on who changed the password
            $this->logger->log("Password changed for user ID: $userId", 'INFO');
            return true;
        }
        
        return false;
    }
    
    public function deleteUser($userId) {
        $userId = intval($userId);
        
        $sql = "DELETE FROM users WHERE id = $userId";
        $result = $this->db->query($sql);
        
        // No logging at all for user deletion
        
        return $result;
    }
}
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Attempt multiple login failures with different usernames (e.g., admin, administrator, root)
2. Successfully login after several attempts
3. Perform sensitive actions like deleting users or resetting passwords
4. Log out and check the system for logs - no failed login attempts were recorded, no audit trail exists for the sensitive actions taken

### Hard Example PoC
1. Access the system as an administrator
2. Update critical user information (email, role, etc.) for another user
3. Change another user's password
4. Delete a user account
5. Check the system logs - notice there are minimal or insufficient logs for these critical actions
6. If you can access the server, check if any alerts would have been triggered:
   - No alerts are sent for multiple failed login attempts
   - Password changes don't include who changed the password
   - User account deletion events are not logged at all

## Security Fix Recommendations

### For Easy Example:
```php
function log_activity($message, $level = 'INFO', $user = null) {
    $log_file = 'logs/app_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user = $user ?? ($_SESSION['username'] ?? 'unknown');
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_message = "[$timestamp] [$level] [User:$user] [IP:$ip] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Log login attempt
        log_activity("Login attempt for user: $username", 'INFO', $username);
        
        if (isset($users[$username]) && $users[$username] === $password) {
            $_SESSION['username'] = $username;
            
            // Log successful login
            log_activity("Login successful for user: $username", 'INFO', $username);
            
            header('Location: dashboard.php');
            exit;
        } else {
            // Log failed login
            log_activity("Failed login for user: $username", 'WARNING', $username);
            
            $error = 'Invalid username or password';
        }
    }
}

// In dashboard.php
function delete_user($userId) {
    // In a real application, this would delete a user from the database
    $targetUser = getUserById($userId); // Get user info for logging
    
    // Perform deletion
    $result = true; // Assumed successful for this example
    
    // Log the critical action with details
    $actingUser = $_SESSION['username'] ?? 'unknown';
    log_activity("User deletion: User ID $userId (" . ($targetUser['username'] ?? 'unknown') . ") deleted by $actingUser", 'CRITICAL');
    
    return $result;
}

function reset_password($userId) {
    // In a real application, this would reset a user's password
    $targetUser = getUserById($userId); // Get user info for logging
    
    // Perform password reset
    $result = true; // Assumed successful for this example
    
    // Log the critical action with details
    $actingUser = $_SESSION['username'] ?? 'unknown';
    log_activity("Password reset: User ID $userId (" . ($targetUser['username'] ?? 'unknown') . ") password reset by $actingUser", 'CRITICAL');
    
    return $result;
}
```

### For Hard Example:
```php
class UserManager {
    private $db;
    private $logger;
    
    public function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function updateProfile($userId, $data) {
        // Get original values for logging
        $originalUser = $this->getUserById($userId);
        $changedFields = [];
        $changes = [];
        
        // Track which fields are being changed
        foreach ($data as $field => $value) {
            if (isset($originalUser[$field]) && $originalUser[$field] !== $value) {
                $changedFields[$field] = [
                    'old' => $originalUser[$field],
                    'new' => $value
                ];
            }
            
            $field = $this->db->escape($field);
            $value = $this->db->escape($value);
            $changes[] = "$field = '$value'";
        }
        
        if (empty($changes)) {
            return false;
        }
        
        $changesStr = implode(', ', $changes);
        $sql = "UPDATE users SET $changesStr WHERE id = $userId";
        $result = $this->db->query($sql);
        
        if ($result) {
            // Log the action with details of what changed
            $actingUserId = $_SESSION['user_id'] ?? 0;
            $actingUsername = $_SESSION['username'] ?? 'system';
            
            // Convert changed fields to a JSON string for logging
            $changedFieldsJson = json_encode($changedFields);
            
            $this->logger->log(
                "User profile updated: ID $userId updated by user ID $actingUserId ($actingUsername). " .
                "Changed fields: $changedFieldsJson", 
                'INFO'
            );
            
            // For sensitive field changes (e.g., email, role), log at a higher level
            if (isset($changedFields['email']) || isset($changedFields['role'])) {
                $this->logger->log(
                    "SENSITIVE field change: User ID $userId had email or role changed by $actingUsername",
                    'WARNING'
                );
                
                // For role changes to admin, log at highest level
                if (isset($changedFields['role']) && $changedFields['role']['new'] === 'admin') {
                    $this->logger->log(
                        "CRITICAL role change: User ID $userId promoted to admin by $actingUsername",
                        'CRITICAL'
                    );
                }
            }
        }
        
        return $result;
    }
    
    public function changePassword($userId, $newPassword) {
        $userId = intval($userId);
        $newPassword = $this->db->escape($newPassword);
        
        $sql = "UPDATE users SET password = '$newPassword' WHERE id = $userId";
        $result = $this->db->query($sql);
        
        if ($result) {
            // Detailed logging of password change
            $targetUser = $this->getUserById($userId);
            $actingUserId = $_SESSION['user_id'] ?? 0;
            $actingUsername = $_SESSION['username'] ?? 'system';
            $isAdmin = $_SESSION['role'] ?? '' === 'admin';
            $isSelfUpdate = ($actingUserId === $userId);
            
            $this->logger->log(
                "Password changed for user ID: $userId (" . ($targetUser['username'] ?? 'unknown') . ") " .
                "by user ID: $actingUserId ($actingUsername). " .
                "Self update: " . ($isSelfUpdate ? 'Yes' : 'No') . ", " .
                "By admin: " . ($isAdmin ? 'Yes' : 'No'),
                'WARNING'
            );
            
            // If the password was changed by someone other than the user or an admin, log as CRITICAL
            if (!$isSelfUpdate && !$isAdmin) {
                $this->logger->log(
                    "SUSPICIOUS password change: User ID $userId password changed by non-admin user $actingUserId",
                    'CRITICAL'
                );
                
                // This should trigger an alert
                $this->sendAlert("Suspicious password change detected", [
                    'target_user' => $userId,
                    'acting_user' => $actingUserId,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
            
            return true;
        }
        
        return false;
    }
    
    public function deleteUser($userId) {
        $userId = intval($userId);
        
        // Get user details before deletion for logging
        $targetUser = $this->getUserById($userId);
        
        $sql = "DELETE FROM users WHERE id = $userId";
        $result = $this->db->query($sql);
        
        if ($result) {
            // Comprehensive logging for user deletion
            $actingUserId = $_SESSION['user_id'] ?? 0;
            $actingUsername = $_SESSION['username'] ?? 'system';
            $targetUsername = $targetUser['username'] ?? 'unknown';
            $targetEmail = $targetUser['email'] ?? 'unknown';
            $targetRole = $targetUser['role'] ?? 'unknown';
            
            $this->logger->log(
                "User deleted: ID $userId (Username: $targetUsername, Email: $targetEmail, Role: $targetRole) " .
                "deleted by user ID $actingUserId ($actingUsername)",
                'CRITICAL'
            );
            
            // This should trigger an alert
            $this->sendAlert("User account deleted", [
                'target_user' => [
                    'id' => $userId,
                    'username' => $targetUsername,
                    'email' => $targetEmail,
                    'role' => $targetRole
                ],
                'acting_user' => [
                    'id' => $actingUserId,
                    'username' => $actingUsername
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $result;
    }
    
    private function sendAlert($message, $data) {
        // Implementation would depend on the alert system used (email, SMS, webhook, etc.)
    }
}
```

## Additional Security Measures

1. **SIEM Integration**: Forward logs to a Security Information and Event Management system
2. **Centralized Logging**: Implement centralized logging across all application components
3. **Log Monitoring Tools**: Use automated tools to analyze logs in real-time
4. **Log Integrity**: Ensure logs are immutable and cannot be tampered with
5. **Security Alerting**: Set up alerting thresholds and escalation procedures
6. **Log Format Standards**: Use standardized log formats like Common Event Format (CEF) or Log Event Extended Format (LEEF)
7. **Correlation**: Correlate events across different parts of the application and infrastructure 