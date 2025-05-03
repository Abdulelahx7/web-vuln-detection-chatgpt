<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: easy_vulnerable.php');
    exit;
}

$username = $_SESSION['username'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'] ?? 0;
        delete_user($userId);
    } elseif (isset($_POST['reset_password'])) {
        $userId = $_POST['user_id'] ?? 0;
        reset_password($userId);
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header('Location: easy_vulnerable.php');
        exit;
    }
}

$users = [
    1 => ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com', 'role' => 'Administrator'],
    2 => ['id' => 2, 'username' => 'user', 'email' => 'user@example.com', 'role' => 'User'],
    3 => ['id' => 3, 'username' => 'jane', 'email' => 'jane@example.com', 'role' => 'User'],
    4 => ['id' => 4, 'username' => 'john', 'email' => 'john@example.com', 'role' => 'User']
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .actions { display: flex; gap: 5px; }
        button { padding: 5px 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button.delete { background-color: #f44336; }
        button.reset { background-color: #2196F3; }
        .logout { padding: 8px 15px; background-color: #ccc; color: #333; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
                <form method="post" action="" style="display: inline; margin-left: 10px;">
                    <button type="submit" name="logout" class="logout">Logout</button>
                </form>
            </div>
        </div>
        
        <h2>User Management</h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="actions">
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="reset_password" class="reset">Reset Password</button>
                            </form>
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 