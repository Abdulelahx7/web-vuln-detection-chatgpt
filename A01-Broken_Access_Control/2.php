<?php
session_start();

$db_users = [
    1 => ['username' => 'regular_user', 'role' => 'user', 'profile_id' => 1],
    2 => ['username' => 'admin_user', 'role' => 'admin', 'profile_id' => 2]
];

$db_profiles = [
    1 => ['name' => 'Regular User', 'email' => 'regular@example.com'],
    2 => ['name' => 'Admin User', 'email' => 'admin@example.com'],
    3 => ['name' => 'CEO', 'email' => 'ceo@example.com', 'salary' => '$500,000', 'ssn' => '123-45-6789']
];

$user_id = 1;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

if (!isset($_GET['action'])) {
    $_GET['action'] = 'view_profile';
}

$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $db_users[$user_id]['profile_id'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
</head>
<body>
    <h1>User Profile System</h1>
    
    <?php if ($_GET['action'] == 'view_profile' && isset($db_profiles[$profile_id])): ?>
        <div>
            <h3>Profile Details:</h3>
            <ul>
                <?php foreach($db_profiles[$profile_id] as $key => $value): ?>
                    <li><?php echo ucfirst($key) . ': ' . $value; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div>
        <p>View profiles:</p>
        <ul>
            <li><a href="hard_vulnerable.php?action=view_profile&id=1">View Profile 1</a></li>
            <li><a href="hard_vulnerable.php?action=view_profile&id=2">View Profile 2</a></li>
        </ul>
    </div>
    
    <div>
        <p>Switch user:</p>
        <form method="post" action="hard_vulnerable.php">
            <input type="hidden" name="set_user" value="1">
            <button type="submit">Login as regular user</button>
        </form>
        <form method="post" action="hard_vulnerable.php">
            <input type="hidden" name="set_user" value="2">
            <button type="submit">Login as admin</button>
        </form>
    </div>
    
    <?php
    if (isset($_POST['set_user'])) {
        $_SESSION['user_id'] = intval($_POST['set_user']);
        header('Location: hard_vulnerable.php');
        exit;
    }
    ?>
</body>
</html> 