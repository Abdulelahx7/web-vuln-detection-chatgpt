<?php
session_start();

class Database {
    private $conn;
    
    public function __construct($host, $username, $password, $database) {
        $this->conn = new mysqli($host, $username, $password, $database);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function close() {
        $this->conn->close();
    }
}

class Logger {
    private $logFile;
    private $logLevel;
    private $logLevels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3, 'CRITICAL' => 4];
    
    public function __construct($logFile = 'app.log', $logLevel = 'INFO') {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
    }
    
    public function log($message, $level = 'INFO') {
        if ($this->shouldLog($level)) {
            $timestamp = date('Y-m-d H:i:s');
            $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
            file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
        }
    }
    
    private function shouldLog($level) {
        return $this->logLevels[$level] >= $this->logLevels[$this->logLevel];
    }
}

class UserManager {
    private $db;
    private $logger;
    
    public function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function authenticate($username, $password) {
        $username = $this->db->escape($username);
        // Insecure query - vulnerable to SQL injection
        $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $this->logger->log("User login: {$user['username']}", 'INFO');
            return $user;
        }
        
        $this->logger->log("Failed login attempt for username: $username", 'DEBUG');
        return null;
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

class AdminPanel {
    private $userManager;
    
    public function __construct(UserManager $userManager) {
        $this->userManager = $userManager;
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? 'home';
        
        switch ($action) {
            case 'update_profile':
                return $this->handleUpdateProfile();
            case 'change_password':
                return $this->handleChangePassword();
            case 'delete_user':
                return $this->handleDeleteUser();
            default:
                return $this->renderHome();
        }
    }
    
    private function handleUpdateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->renderUpdateProfileForm();
        }
        
        $userId = $_POST['user_id'] ?? 0;
        $data = [
            'email' => $_POST['email'] ?? '',
            'full_name' => $_POST['full_name'] ?? '',
            'role' => $_POST['role'] ?? 'user'
        ];
        
        $result = $this->userManager->updateProfile($userId, $data);
        
        if ($result) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    private function handleChangePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->renderChangePasswordForm();
        }
        
        $userId = $_POST['user_id'] ?? 0;
        $newPassword = $_POST['new_password'] ?? '';
        
        $result = $this->userManager->changePassword($userId, $newPassword);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    private function handleDeleteUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        $userId = $_POST['user_id'] ?? 0;
        
        $result = $this->userManager->deleteUser($userId);
        
        if ($result) {
            return ['success' => true, 'message' => 'User deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }
    
    private function renderHome() {
        return ['view' => 'home'];
    }
    
    private function renderUpdateProfileForm() {
        return ['view' => 'update_profile'];
    }
    
    private function renderChangePasswordForm() {
        return ['view' => 'change_password'];
    }
}

// Initialize the application
// In a real application, these would use actual database credentials
$db = new Database('localhost', 'app_user', 'app_password', 'app_database');
$logger = new Logger('app.log', 'INFO');
$userManager = new UserManager($db, $logger);
$adminPanel = new AdminPanel($userManager);

// Handle the request (simulation only)
$result = $adminPanel->handleRequest();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1, h2 { color: #333; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"], input[type="email"], select { width: 100%; padding: 8px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; }
        .panel-header { background-color: #f5f5f5; padding: 10px; margin: -15px -15px 15px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>
        
        <?php if (isset($result['success'])): ?>
            <div class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="panel">
            <div class="panel-header">
                <h2>User Management</h2>
            </div>
            
            <h3>Update User Profile</h3>
            <form method="post" action="?action=update_profile">
                <div class="form-group">
                    <label for="user_id">User ID:</label>
                    <input type="text" id="user_id" name="user_id" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role">
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                
                <button type="submit">Update Profile</button>
            </form>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Change User Password</h2>
            </div>
            
            <form method="post" action="?action=change_password">
                <div class="form-group">
                    <label for="change_user_id">User ID:</label>
                    <input type="text" id="change_user_id" name="user_id" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <button type="submit">Change Password</button>
            </form>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Delete User</h2>
            </div>
            
            <form method="post" action="?action=delete_user">
                <div class="form-group">
                    <label for="delete_user_id">User ID:</label>
                    <input type="text" id="delete_user_id" name="user_id" required>
                </div>
                
                <button type="submit" style="background-color: #f44336;">Delete User</button>
            </form>
        </div>
    </div>
</body>
</html> 