# A03: Injection

## Description
Injection vulnerabilities, such as SQL, NoSQL, OS, and LDAP injection, occur when untrusted data is sent to an interpreter as part of a command or query. The attacker's hostile data can trick the interpreter into executing unintended commands or accessing data without proper authorization.

SQL Injection is one of the most common and dangerous types of injection, where an attacker manipulates SQL queries by injecting malicious SQL code into input fields.

## Vulnerable Code Examples

### Easy Example: Basic SQL Injection

This code directly includes user input in an SQL query without proper sanitization:

```php
$search = $_GET['search'];

$query = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%'";
$result = $conn->query($query);
```

### Hard Example: Second-Order Injection

This code uses a vulnerable `generateUserReport` function that doesn't properly sanitize the filter parameter, allowing SQL injection:

```php
public function generateUserReport($userId, $filter = '') {
    $filterClause = '';
    if (!empty($filter)) {
        $filterClause = "AND action = '$filter'";
    }
    
    $query = "SELECT * FROM user_activity WHERE user_id = $userId $filterClause ORDER BY timestamp DESC";
    $result = $this->db->executeQuery($query);
    
    return $this->db->fetchAll($result);
}

// Later used with user input
$filter = $_POST['filter'] ?? '';
$reportData = $userService->generateUserReport($_SESSION['user_id'], $filter);
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Visit the product search page normally: `easy_vulnerable.php`
2. Enter the search term: `' OR 1=1 --`
3. The resulting SQL query becomes: `SELECT * FROM products WHERE name LIKE '%' OR 1=1 --%' OR description LIKE '%' OR 1=1 --%'`
4. This returns all products in the database, bypassing the search filter

### Hard Example PoC
1. Register and log in to the system
2. Go to the "Generate Activity Report" section
3. Enter this in the filter field: `' UNION SELECT username, password, email FROM users --`
4. The report will now include user credentials from the users table, although it was only meant to show activity data

## Security Fix Recommendations

### For Easy Example:
```php
// Use prepared statements to properly separate code from data
$search = $_GET['search'];

$stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
$searchParam = "%" . $search . "%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
```

### For Hard Example:
```php
public function generateUserReport($userId, $filter = '') {
    $query = "SELECT * FROM user_activity WHERE user_id = ? ";
    $params = [$userId];
    
    if (!empty($filter)) {
        $query .= "AND action = ? ";
        $params[] = $filter;
    }
    
    $query .= "ORDER BY timestamp DESC";
    
    $stmt = $this->db->prepare($query);
    // Bind each parameter with its appropriate type
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $this->db->fetchAll($result);
}
```

## Additional Security Measures

1. **Input Validation**: Validate all user inputs against a whitelist of allowed characters
2. **Parameterized Queries**: Always use prepared statements with parameterized queries
3. **Least Privilege**: Database users should have the minimum necessary privileges
4. **Error Handling**: Avoid exposing SQL errors to the user
5. **ORM Frameworks**: Consider using ORM frameworks that handle SQL escaping automatically 