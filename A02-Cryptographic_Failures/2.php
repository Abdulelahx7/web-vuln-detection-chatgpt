<?php
session_start();

class Database {
    private $connection;
    
    public function __construct() {
        $this->connection = new mysqli('localhost', 'db_user', 'db_password', 'secure_app');
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    public function close() {
        $this->connection->close();
    }
}

class UserManager {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
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
    
    public function registerUser($email, $password, $name) {
        $email = $this->db->escape($email);
        $name = $this->db->escape($name);
        $password_hash = hash('sha256', $password . 'salt1234');
        
        $query = "INSERT INTO users (email, password, name) VALUES ('$email', '$password_hash', '$name')";
        return $this->db->query($query);
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $simulation_mode = true;
    
    if (isset($_POST['register'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $name = $_POST['name'] ?? '';
        
        if (!$simulation_mode) {
            $db = new Database();
            $userManager = new UserManager($db);
            $result = $userManager->registerUser($email, $password, $name);
            $db->close();
            
            if ($result) {
                $success = "User registered successfully!";
            } else {
                $error = "Failed to register user";
            }
        } else {
            if (!empty($email) && !empty($password) && !empty($name)) {
                $success = "User registered successfully! (Simulation Mode)";
            } else {
                $error = "All fields are required";
            }
        }
    } elseif (isset($_POST['login'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!$simulation_mode) {
            $db = new Database();
            $userManager = new UserManager($db);
            $user = $userManager->authenticate($email, $password);
            $db->close();
            
            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid email or password";
            }
        } else {
            if ($email === 'admin@example.com' && $password === 'admin123') {
                $success = "Login successful! (Simulation Mode)";
            } else {
                $error = "Invalid email or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Authentication System</title>
    <style>
        .container { max-width: 500px; margin: 0 auto; padding: 20px; }
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .tab { display: none; }
        .tab-active { display: block; }
        .tabs { margin-bottom: 20px; }
        .tab-link { display: inline-block; padding: 10px 15px; background: #f1f1f1; cursor: pointer; }
        .tab-link.active { background: #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Authentication System</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab-link active" onclick="openTab('login')">Login</div>
            <div class="tab-link" onclick="openTab('register')">Register</div>
        </div>
        
        <div id="login" class="tab tab-active">
            <h2>Login</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="login-email">Email:</label>
                    <input type="email" id="login-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password:</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="login">Login</button>
                </div>
            </form>
        </div>
        
        <div id="register" class="tab">
            <h2>Register</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="register-name">Name:</label>
                    <input type="text" id="register-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email:</label>
                    <input type="email" id="register-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password:</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="register">Register</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openTab(tabName) {
        var tabs = document.getElementsByClassName('tab');
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].style.display = 'none';
        }
        
        var tabLinks = document.getElementsByClassName('tab-link');
        for (var i = 0; i < tabLinks.length; i++) {
            tabLinks[i].className = tabLinks[i].className.replace(' active', '');
        }
        
        document.getElementById(tabName).style.display = 'block';
        event.currentTarget.className += ' active';
    }
    </script>
</body>
</html> 