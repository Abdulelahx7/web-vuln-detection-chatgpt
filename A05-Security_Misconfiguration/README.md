# A05: Security Misconfiguration

## Description
Security Misconfiguration happens when security settings are defined, implemented, or maintained improperly. It's one of the most common vulnerabilities and can occur at any level of the application stack, including network services, platform, web server, application server, database, frameworks, custom code, and pre-installed virtual machines, containers, or storage.

Common security misconfigurations include:
- Unnecessary features enabled or installed (e.g., unnecessary ports, services, pages, accounts, or privileges)
- Default accounts with unchanged passwords
- Error handling that reveals too much information to users
- Running outdated or vulnerable software
- Security settings in application servers, frameworks, and libraries not set to secure values
- Missing or misconfigured security headers or directives
- Unnecessary exposure of sensitive information in error messages

## Vulnerable Code Examples

### Easy Example: Exposing Configuration Details and Debugging Information

This code displays sensitive configuration information and PHP details on an admin page:

```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => 'p@ssw0rd123',
    'db_name' => 'app_database',
    'api_key' => 'sk_test_51LcGzhDJ7jXMHzDxHjbkC7Xn4wdZJT8VGSmVNwk1pOYZB7p6qVaeyLnGOEzwHqPZhJkR3h5LHtI6PB9',
    'admin_email' => 'admin@example.com',
    'debug_mode' => true
];

// Later in the code, displaying this information:
<table>
    <tr>
        <th>Configuration</th>
        <th>Value</th>
    </tr>
    <?php foreach ($config as $key => $value): ?>
        <tr>
            <td><?php echo htmlspecialchars($key); ?></td>
            <td><?php echo htmlspecialchars($value); ?></td>
        </tr>
    <?php endforeach; ?>
</table>

// Displaying PHP information with phpinfo()
<?php phpinfo(); ?>
```

### Hard Example: Debug Console Exposed in Production and Insecure File Management

This code leaves a debug console enabled in what should be a production environment:

```php
define('APP_ENV', 'development');

// Later in the code:
<?php if (APP_ENV === 'development'): ?>
    <div style="position: fixed; bottom: 0; left: 0; right: 0; background: #f1f1f1; border-top: 1px solid #ddd; padding: 10px; font-family: monospace; font-size: 12px;">
        <strong>Debug Console</strong> (Remove in production!)
        <div>Session ID: <?php echo session_id(); ?></div>
        <div>User IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></div>
        <div>User Agent: <?php echo $_SERVER['HTTP_USER_AGENT']; ?></div>
        <div>
            Request Data:
            <pre><?php print_r($_REQUEST); ?></pre>
        </div>
    </div>
<?php endif; ?>

// Also insecure file upload configuration:
if (!is_dir($this->uploadDirectory)) {
    mkdir($this->uploadDirectory, 0777, true);
}
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Visit the admin dashboard page at `easy_vulnerable.php`
2. You can see all system configuration details, including database credentials and API keys
3. Scroll down to see the full PHP info output, which reveals server software versions, PHP settings, and other sensitive information that could be used to plan attacks

### Hard Example PoC
1. Visit the administration panel at `hard_vulnerable.php`
2. At the bottom of the page, notice the debug console is active showing session ID, request data, and other sensitive information
3. Upload a PHP file through the file upload form (the application doesn't validate file types)
4. The uploaded PHP file can then be executed since the upload directory has insecure permissions (0777)

## Security Fix Recommendations

### For Easy Example:
```php
// Set environment-specific configurations
$environment = 'production'; // Should be set based on deployment environment

// Only enable error display in development
if ($environment === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Load configuration from a secure location outside web root
$config = require_once('/path/outside/webroot/config.php');

// Never display sensitive configuration in production
if ($environment === 'production') {
    // Only show non-sensitive configuration values
    $safeConfig = [
        'debug_mode' => $config['debug_mode'],
        'app_version' => $config['app_version']
    ];
}

// Remove phpinfo() completely from production code
```

### For Hard Example:
```php
// Define environment constant based on deployment environment
define('APP_ENV', getenv('APP_ENVIRONMENT') ?: 'production');

// Use proper file upload security
private function uploadFile($file) {
    // Validate file type and extension
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    
    $fileType = $file['type'];
    $fileName = basename($file['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid file type. Only images and PDFs are allowed.');
    }
    
    // Use a secure directory with proper permissions
    if (!is_dir($this->uploadDirectory)) {
        mkdir($this->uploadDirectory, 0755, true);
    }
    
    // Generate a random file name to prevent overwriting
    $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
    $targetPath = $this->uploadDirectory . '/' . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $newFileName;
    }
    
    return false;
}

// Remove debug console from production
// The debug console should only be included conditionally:
<?php if (APP_ENV === 'development' && $_SERVER['REMOTE_ADDR'] === '127.0.0.1'): ?>
    <!-- Debug console code here -->
<?php endif; ?>
```

## Additional Security Measures

1. **Security Headers**: Implement proper security headers like Content-Security-Policy, X-Content-Type-Options, etc.
2. **Version Management**: Remove version information from headers and error messages
3. **Configuration Auditing**: Regularly audit and test security configurations
4. **Least Privilege**: Apply the principle of least privilege to all system components
5. **Secure Deployment**: Use a proper deployment checklist to ensure staging/debug settings are disabled in production 