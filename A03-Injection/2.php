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
    
    public function executeQuery($query) {
        $result = $this->conn->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $this->conn->error);
        }
        
        return $result;
    }
    
    public function fetchAll($result) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function escape($value) {
        return $this->conn->real_escape_string($value);
    }
    
    public function close() {
        $this->conn->close();
    }
}

class UserService {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    public function registerUser($username, $email, $password) {
        $username = $this->db->escape($username);
        $email = $this->db->escape($email);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password_hash')";
        $this->db->executeQuery($query);
        
        return $this->getUserByUsername($username);
    }
    
    public function getUserByUsername($username) {
        $username = $this->db->escape($username);
        
        $query = "SELECT id, username, email FROM users WHERE username = '$username'";
        $result = $this->db->executeQuery($query);
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    public function getAllUserActivities($userId) {
        $query = "SELECT * FROM user_activity WHERE user_id = $userId ORDER BY timestamp DESC";
        $result = $this->db->executeQuery($query);
        
        return $this->db->fetchAll($result);
    }
    
    public function recordActivity($userId, $action, $details = '') {
        $action = $this->db->escape($action);
        $details = $this->db->escape($details);
        
        $query = "INSERT INTO user_activity (user_id, action, details) VALUES ($userId, '$action', '$details')";
        $this->db->executeQuery($query);
    }
    
    public function generateUserReport($userId, $filter = '') {
        $filterClause = '';
        if (!empty($filter)) {
            $filterClause = "AND action = '$filter'";
        }
        
        $query = "SELECT * FROM user_activity WHERE user_id = $userId $filterClause ORDER BY timestamp DESC";
        $result = $this->db->executeQuery($query);
        
        return $this->db->fetchAll($result);
    }
}

$db = new Database('localhost', 'db_user', 'db_password', 'security_demo');
$userService = new UserService($db);

$message = '';
$error = '';
$registeredUser = null;
$userActivities = [];
$reportData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required";
        } else {
            try {
                $registeredUser = $userService->registerUser($username, $email, $password);
                $userService->recordActivity($registeredUser['id'], 'register', 'User registered');
                $message = "User registered successfully!";
                $_SESSION['user_id'] = $registeredUser['id'];
                $_SESSION['username'] = $registeredUser['username'];
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['generate_report']) && isset($_SESSION['user_id'])) {
        $filter = $_POST['filter'] ?? '';
        $reportData = $userService->generateUserReport($_SESSION['user_id'], $filter);
    }
}

if (isset($_SESSION['user_id'])) {
    $userActivities = $userService->getAllUserActivities($_SESSION['user_id']);
}

$db->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration & Activity System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .section { margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Registration & Activity System</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div>
                <h2>Register</h2>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="register">Register</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="section">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                
                <div class="section">
                    <h3>Your Activity</h3>
                    <?php if (!empty($userActivities)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userActivities as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['timestamp']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No activity recorded yet.</p>
                    <?php endif; ?>
                </div>
                
                <div class="section">
                    <h3>Generate Activity Report</h3>
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="filter">Filter by Action:</label>
                            <input type="text" id="filter" name="filter">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="generate_report">Generate Report</button>
                        </div>
                    </form>
                    
                    <?php if (!empty($reportData)): ?>
                        <h4>Report Results</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['timestamp']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 