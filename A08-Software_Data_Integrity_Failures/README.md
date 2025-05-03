# A08: Software and Data Integrity Failures

## Description
Software and Data Integrity Failures relate to code and infrastructure that does not protect against integrity violations. This can include using plugins, libraries, or modules from untrusted sources, using insecure CI/CD pipelines without proper signing or verification, and explicitly or implicitly trusting serialized data. An integrity failure occurs when an application assumes that user input or software from any source is trustworthy without verification.

Common examples include:
- Using plugins, libraries, or modules from untrusted sources
- Deserialization of untrusted data without proper validation
- Auto-update functionality without proper integrity verification
- Reliance on unsigned or unverified code or data

## Vulnerable Code Examples

### Easy Example: Insecure Deserialization

This code directly deserializes user-controllable data without validation:

```php
if (isset($_GET['load'])) {
    $filename = $_GET['load'];
    if (file_exists($filename)) {
        $serializedData = file_get_contents($filename);
        $data = unserialize($serializedData);
        $message = "Data loaded from $filename";
    } else {
        $error = "File $filename not found";
    }
}
```

### Hard Example: Unsafe Plugin System Using Eval

This code implements a plugin system that uses `eval()` to execute untrusted code:

```php
public function installPlugin($pluginData) {
    $pluginName = $pluginData['name'] ?? null;
    $pluginCode = $pluginData['code'] ?? null;
    
    if (empty($pluginName) || empty($pluginCode)) {
        return false;
    }
    
    if (!is_dir($this->pluginsDirectory)) {
        mkdir($this->pluginsDirectory, 0755, true);
    }
    
    $pluginFileName = $this->pluginsDirectory . '/' . $pluginName . '.php';
    file_put_contents($pluginFileName, $pluginCode);
    
    $this->plugins[$pluginName] = [
        'name' => $pluginName,
        'file' => $pluginFileName,
        'installed' => date('Y-m-d H:i:s')
    ];
    
    // Execute plugin initialization code
    eval($pluginCode);
    
    return true;
}
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Create a malicious serialized data file named `exploit.txt` with the following content:
   ```php
   O:8:"stdClass":2:{s:4:"name";s:6:"hacker";s:9:"__wakeup";a:1:{i:0;s:34:"system('echo vulnerable > pwn.txt');";}}
   ```
2. Visit the page with parameter: `easy_vulnerable.php?load=exploit.txt`
3. The application will deserialize this object and execute the PHP `system()` function
4. A file named `pwn.txt` with content "vulnerable" will be created on the server

### Hard Example PoC
1. Create a plugin with the following code:
   ```php
   <?php
   system('id'); // Execute arbitrary command
   file_put_contents('/tmp/backdoor.php', '<?php system($_GET["cmd"]); ?>'); // Create backdoor
   ?>
   ```
2. Submit this plugin through the plugin installation interface
3. The application will save the plugin file and execute it using `eval()`
4. This will execute the `system('id')` command and create a backdoor file that allows remote command execution

## Security Fix Recommendations

### For Easy Example:
```php
if (isset($_GET['load'])) {
    $filename = $_GET['load'];
    
    // Only allow loading from specific directory with strict filename validation
    $safeDirectory = 'data/';
    $pattern = '/^[a-zA-Z0-9_-]+\.txt$/';
    
    if (!preg_match($pattern, basename($filename))) {
        $error = "Invalid filename format";
    } else {
        $fullPath = $safeDirectory . basename($filename);
        
        if (file_exists($fullPath)) {
            $serializedData = file_get_contents($fullPath);
            
            // Use a safer alternative to unserialize
            try {
                // Option 1: Use a JSON format instead of PHP serialization
                $data = json_decode(file_get_contents($fullPath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON data");
                }
                
                // Option 2: If PHP serialization must be used, use allowed_classes
                // $data = unserialize($serializedData, ['allowed_classes' => false]);
                
                $message = "Data loaded from $filename";
            } catch (Exception $e) {
                $error = "Error loading data: " . $e->getMessage();
            }
        } else {
            $error = "File not found";
        }
    }
}
```

### For Hard Example:
```php
public function installPlugin($pluginData) {
    $pluginName = $pluginData['name'] ?? null;
    $pluginCode = $pluginData['code'] ?? null;
    
    if (empty($pluginName) || empty($pluginCode)) {
        return false;
    }
    
    // Validate plugin name
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $pluginName)) {
        throw new Exception("Invalid plugin name");
    }
    
    // Never use eval for plugin code
    // Instead, implement a secure plugin architecture with:
    // 1. Code signature verification
    $signature = $pluginData['signature'] ?? null;
    if (!$this->verifyPluginSignature($pluginCode, $signature)) {
        throw new Exception("Plugin signature verification failed");
    }
    
    // 2. Sandboxing & restricted capabilities
    $analyzer = new CodeAnalyzer();
    if (!$analyzer->isSafe($pluginCode)) {
        throw new Exception("Plugin contains potentially unsafe code");
    }
    
    // 3. Plugin interface with clearly defined hooks
    if (!$this->validatePluginInterface($pluginCode)) {
        throw new Exception("Plugin does not implement required interfaces");
    }
    
    // 4. Store the plugin
    if (!is_dir($this->pluginsDirectory)) {
        mkdir($this->pluginsDirectory, 0755, true);
    }
    
    $pluginFileName = $this->pluginsDirectory . '/' . $pluginName . '.php';
    file_put_contents($pluginFileName, $pluginCode);
    
    $this->plugins[$pluginName] = [
        'name' => $pluginName,
        'file' => $pluginFileName,
        'installed' => date('Y-m-d H:i:s')
    ];
    
    // 5. Load the plugin through proper class autoloading
    // Never use eval() or include() directly with user-provided code
    return true;
}

// Helper function to load plugins safely
public function loadPlugin($pluginName) {
    if (!isset($this->plugins[$pluginName])) {
        return false;
    }
    
    $pluginInfo = $this->plugins[$pluginName];
    $pluginClassName = 'Plugin' . ucfirst($pluginName);
    
    // Use a class autoloader instead of direct include
    $plugin = new $pluginClassName();
    
    // Verify the plugin implements the required interface
    if (!($plugin instanceof PluginInterface)) {
        throw new Exception("Invalid plugin: does not implement PluginInterface");
    }
    
    // Register the plugin with limited capabilities
    $this->activePlugins[$pluginName] = $plugin;
    
    return $plugin;
}
```

## Additional Security Measures

1. **Integrity Checking**: Implement digital signatures and hash verification for all code or configuration loaded from external sources
2. **Trusted Sources**: Only use libraries, plugins and modules from trusted and verified sources
3. **Safe Deserialization**: Use safe alternatives to PHP's `unserialize()` such as JSON or restrict allowed classes
4. **CI/CD Pipeline Security**: Secure build and deployment pipelines with proper code signing and verification
5. **Sandboxing**: Use isolation and sandboxing when executing untrusted code
6. **Whitelisting**: Implement an allowlist approach for plugin functionality and capabilities 