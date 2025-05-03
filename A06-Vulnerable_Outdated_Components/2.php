<?php
// Import third-party libraries (simulated)
require_once 'vendor/autoload.php';

class UserAuthenticator {
    private $userRepository;
    private $crypto;
    
    public function __construct($userRepository, $crypto) {
        $this->userRepository = $userRepository;
        $this->crypto = $crypto;
    }
    
    public function authenticate($username, $password) {
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        $hashedPassword = $this->crypto->hash($password);
        
        return $user['password'] === $hashedPassword;
    }
}

class UserRepository {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function findByUsername($username) {
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
}

class CryptoService {
    private $algorithm;
    
    public function __construct($algorithm = 'md5') {
        $this->algorithm = $algorithm;
    }
    
    public function hash($data) {
        return hash($this->algorithm, $data);
    }
}

class Database {
    private $conn;
    
    public function __construct($host, $username, $password, $database) {
        $this->conn = new mysqli($host, $username, $password, $database);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
    
    public function prepare($query) {
        return $this->conn->prepare($query);
    }
    
    public function close() {
        $this->conn->close();
    }
}

// Simulating application bootstrap
$db = new Database('localhost', 'app_user', 'app_password', 'app_database');
$userRepository = new UserRepository($db);
$crypto = new CryptoService('md5'); // Using md5 which is vulnerable to collisions
$authenticator = new UserAuthenticator($userRepository, $crypto);

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = "Username and password are required";
        } else {
            $result = $authenticator->authenticate($username, $password);
            
            if ($result) {
                $message = "Login successful!";
            } else {
                $error = "Invalid username or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Authentication</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
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
        <h1>Login Form</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
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
        
        <footer style="margin-top: 50px; font-size: 12px; color: #777;">
            <p>System Version: 1.2.3</p>
            <p>
                <small>Libraries: 
                    <span title="Critical vulnerabilities in this version">CryptoLib v1.0.4</span>, 
                    <span>SecurityUtils v2.1.1</span>, 
                    <span>DBConnector v3.0.2</span>
                </small>
            </p>
            <p><small>© 2023 Example Company</small></p>
        </footer>
    </div>
</body>
</html> 