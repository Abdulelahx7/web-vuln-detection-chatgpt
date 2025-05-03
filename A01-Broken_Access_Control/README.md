# A01: Broken Access Control

## Description
Broken Access Control occurs when restrictions on what authenticated users are allowed to do are not properly enforced. Attackers can exploit these flaws to access unauthorized functionality and/or data, such as accessing other users' accounts, viewing sensitive files, modifying other users' data, or changing access rights.

## Vulnerable Code Examples

### Easy Example: Simple Broken Access Control

This code uses a GET parameter to determine admin status without any real authentication:

```php
$is_admin = false;
if (isset($_GET['admin']) && $_GET['admin'] == 'true') {
    $is_admin = true;
}

if ($is_admin) {
    $sensitive_data = "Credit Card Numbers: 1234-5678-9012-3456, 9876-5432-1098-7654";
} else {
    $sensitive_data = "You don't have access to sensitive data";
}
```

### Hard Example: Insecure Direct Object Reference (IDOR)

This code allows access to any profile by simply changing the ID parameter, without checking if the user should have access:

```php
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $db_users[$user_id]['profile_id'];

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
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Visit the page normally: `easy_vulnerable.php`
2. Note that you see "You don't have access to sensitive data"
3. Now visit: `easy_vulnerable.php?admin=true`
4. You now have admin access and can see the credit card numbers without any authentication

### Hard Example PoC
1. Login as a regular user or browse to: `hard_vulnerable.php`
2. Note that you can only see your own profile (ID 1)
3. Change the URL to: `hard_vulnerable.php?action=view_profile&id=3`
4. You can now see the CEO's profile containing sensitive information (salary, SSN) that should be restricted

## Security Fix Recommendations

### For Easy Example:
```php
// Use proper session-based authentication
session_start();
$is_admin = false;
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $is_admin = true;
}
```

### For Hard Example:
```php
// Check if the current user has permission to access the requested profile
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $db_users[$user_id]['profile_id'];

// Authorize access before showing the data
$authorized = false;
if ($profile_id === $db_users[$user_id]['profile_id'] || 
    $db_users[$user_id]['role'] === 'admin') {
    $authorized = true;
}

if (!$authorized) {
    echo "Access denied";
    exit;
}
``` 