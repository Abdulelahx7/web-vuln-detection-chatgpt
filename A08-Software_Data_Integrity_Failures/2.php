<?php
class Configuration {
    private $settings = [];
    private $configFile = 'config.json';
    
    public function __construct($configFile = null) {
        if ($configFile) {
            $this->configFile = $configFile;
        }
        
        $this->loadConfig();
    }
    
    public function loadConfig() {
        if (file_exists($this->configFile)) {
            $jsonData = file_get_contents($this->configFile);
            $this->settings = json_decode($jsonData, true);
        } else {
            $this->settings = $this->getDefaultSettings();
            $this->saveConfig();
        }
        
        return $this->settings;
    }
    
    public function saveConfig() {
        $jsonData = json_encode($this->settings, JSON_PRETTY_PRINT);
        file_put_contents($this->configFile, $jsonData);
    }
    
    public function getSetting($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    public function setSetting($key, $value) {
        $this->settings[$key] = $value;
    }
    
    public function getSettings() {
        return $this->settings;
    }
    
    public function updateSettings($settings) {
        foreach ($settings as $key => $value) {
            $this->settings[$key] = $value;
        }
    }
    
    private function getDefaultSettings() {
        return [
            'app_name' => 'My Application',
            'version' => '1.0.0',
            'debug_mode' => false,
            'log_level' => 'error',
            'database' => [
                'host' => 'localhost',
                'user' => 'db_user',
                'password' => 'db_password',
                'name' => 'app_db'
            ],
            'email' => [
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 587,
                'smtp_user' => 'user@example.com',
                'smtp_pass' => 'email_password'
            ],
            'theme' => [
                'primary_color' => '#4CAF50',
                'secondary_color' => '#2196F3',
                'font_family' => 'Arial, sans-serif'
            ],
            'installed_plugins' => [
                'analytics' => true,
                'newsletter' => false,
                'social_media' => true
            ]
        ];
    }
}

class PluginManager {
    private $plugins = [];
    private $pluginsDirectory = 'plugins';
    
    public function __construct($pluginsDirectory = null) {
        if ($pluginsDirectory) {
            $this->pluginsDirectory = $pluginsDirectory;
        }
        
        $this->loadPlugins();
    }
    
    public function loadPlugins() {
        if (!is_dir($this->pluginsDirectory)) {
            mkdir($this->pluginsDirectory, 0755, true);
        }
        
        $files = glob($this->pluginsDirectory . '/*.json');
        
        foreach ($files as $file) {
            $pluginData = file_get_contents($file);
            $plugin = json_decode($pluginData, true);
            
            if ($plugin && isset($plugin['name'])) {
                $this->plugins[$plugin['name']] = $plugin;
            }
        }
    }
    
    public function installPlugin($pluginData) {
        if (!isset($pluginData['name'])) {
            return false;
        }
        
        $pluginName = $pluginData['name'];
        $filename = $this->pluginsDirectory . '/' . $pluginName . '.json';
        
        file_put_contents($filename, json_encode($pluginData, JSON_PRETTY_PRINT));
        $this->plugins[$pluginName] = $pluginData;
        
        if (isset($pluginData['install_script'])) {
            eval($pluginData['install_script']);
        }
        
        return true;
    }
    
    public function uninstallPlugin($pluginName) {
        $filename = $this->pluginsDirectory . '/' . $pluginName . '.json';
        
        if (file_exists($filename)) {
            unlink($filename);
        }
        
        if (isset($this->plugins[$pluginName])) {
            if (isset($this->plugins[$pluginName]['uninstall_script'])) {
                eval($this->plugins[$pluginName]['uninstall_script']);
            }
            
            unset($this->plugins[$pluginName]);
        }
    }
    
    public function getPlugins() {
        return $this->plugins;
    }
    
    public function getPlugin($name) {
        return $this->plugins[$name] ?? null;
    }
}

$configuration = new Configuration('app_config.json');
$pluginManager = new PluginManager();

$error = '';
$message = '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'config';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_config'])) {
        $settings = $_POST['settings'] ?? [];
        $configuration->updateSettings($settings);
        $configuration->saveConfig();
        $message = "Configuration saved successfully!";
    } elseif (isset($_POST['install_plugin'])) {
        $pluginData = $_POST['plugin_data'] ?? '';
        
        if (empty($pluginData)) {
            $error = "Plugin data is required";
        } else {
            $plugin = json_decode($pluginData, true);
            
            if (!$plugin) {
                $error = "Invalid JSON data";
            } else {
                if ($pluginManager->installPlugin($plugin)) {
                    $message = "Plugin installed successfully!";
                } else {
                    $error = "Failed to install plugin";
                }
            }
        }
    } elseif (isset($_POST['uninstall_plugin'])) {
        $pluginName = $_POST['plugin_name'] ?? '';
        
        if (empty($pluginName)) {
            $error = "Plugin name is required";
        } else {
            $pluginManager->uninstallPlugin($pluginName);
            $message = "Plugin uninstalled successfully!";
        }
    }
}

$settings = $configuration->getSettings();
$plugins = $pluginManager->getPlugins();

// Sample plugin data
$samplePlugin = [
    'name' => 'sample_plugin',
    'title' => 'Sample Plugin',
    'description' => 'This is a sample plugin for demonstration',
    'version' => '1.0.0',
    'author' => 'John Doe',
    'website' => 'https://example.com',
    'install_script' => '// This would run on plugin installation',
    'uninstall_script' => '// This would run on plugin uninstallation'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>System Administration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1, h2 { color: #333; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"], textarea { width: 100%; padding: 8px; }
        textarea { height: 200px; font-family: monospace; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; }
        .panel-header { background-color: #f5f5f5; padding: 10px; margin: -15px -15px 15px; border-bottom: 1px solid #ddd; }
        .tabs { display: flex; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .tab { padding: 10px 15px; cursor: pointer; }
        .tab.active { background-color: #f5f5f5; border: 1px solid #ddd; border-bottom: none; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .plugin-list { list-style: none; padding: 0; }
        .plugin-item { padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; }
        .plugin-title { font-weight: bold; }
        .plugin-description { color: #666; margin: 5px 0; }
        .plugin-info { font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Administration</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab <?php echo $tab === 'config' ? 'active' : ''; ?>" onclick="location.href='?tab=config'">Configuration</div>
            <div class="tab <?php echo $tab === 'plugins' ? 'active' : ''; ?>" onclick="location.href='?tab=plugins'">Plugins</div>
        </div>
        
        <div class="tab-content <?php echo $tab === 'config' ? 'active' : ''; ?>">
            <div class="panel">
                <div class="panel-header">
                    <h2>Application Configuration</h2>
                </div>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="app_name">Application Name:</label>
                        <input type="text" id="app_name" name="settings[app_name]" value="<?php echo htmlspecialchars($settings['app_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="version">Version:</label>
                        <input type="text" id="version" name="settings[version]" value="<?php echo htmlspecialchars($settings['version'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="debug_mode">Debug Mode:</label>
                        <select id="debug_mode" name="settings[debug_mode]">
                            <option value="0" <?php echo isset($settings['debug_mode']) && !$settings['debug_mode'] ? 'selected' : ''; ?>>Off</option>
                            <option value="1" <?php echo isset($settings['debug_mode']) && $settings['debug_mode'] ? 'selected' : ''; ?>>On</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="log_level">Log Level:</label>
                        <select id="log_level" name="settings[log_level]">
                            <option value="error" <?php echo isset($settings['log_level']) && $settings['log_level'] === 'error' ? 'selected' : ''; ?>>Error</option>
                            <option value="warning" <?php echo isset($settings['log_level']) && $settings['log_level'] === 'warning' ? 'selected' : ''; ?>>Warning</option>
                            <option value="info" <?php echo isset($settings['log_level']) && $settings['log_level'] === 'info' ? 'selected' : ''; ?>>Info</option>
                            <option value="debug" <?php echo isset($settings['log_level']) && $settings['log_level'] === 'debug' ? 'selected' : ''; ?>>Debug</option>
                        </select>
                    </div>
                    
                    <h3>Database Settings</h3>
                    
                    <div class="form-group">
                        <label for="db_host">Host:</label>
                        <input type="text" id="db_host" name="settings[database][host]" value="<?php echo htmlspecialchars($settings['database']['host'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Username:</label>
                        <input type="text" id="db_user" name="settings[database][user]" value="<?php echo htmlspecialchars($settings['database']['user'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_password">Password:</label>
                        <input type="password" id="db_password" name="settings[database][password]" value="<?php echo htmlspecialchars($settings['database']['password'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name:</label>
                        <input type="text" id="db_name" name="settings[database][name]" value="<?php echo htmlspecialchars($settings['database']['name'] ?? ''); ?>">
                    </div>
                    
                    <h3>Email Settings</h3>
                    
                    <div class="form-group">
                        <label for="smtp_host">SMTP Host:</label>
                        <input type="text" id="smtp_host" name="settings[email][smtp_host]" value="<?php echo htmlspecialchars($settings['email']['smtp_host'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_port">SMTP Port:</label>
                        <input type="text" id="smtp_port" name="settings[email][smtp_port]" value="<?php echo htmlspecialchars($settings['email']['smtp_port'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_user">SMTP Username:</label>
                        <input type="text" id="smtp_user" name="settings[email][smtp_user]" value="<?php echo htmlspecialchars($settings['email']['smtp_user'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_pass">SMTP Password:</label>
                        <input type="password" id="smtp_pass" name="settings[email][smtp_pass]" value="<?php echo htmlspecialchars($settings['email']['smtp_pass'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="save_config">Save Configuration</button>
                </form>
            </div>
        </div>
        
        <div class="tab-content <?php echo $tab === 'plugins' ? 'active' : ''; ?>">
            <div class="panel">
                <div class="panel-header">
                    <h2>Installed Plugins</h2>
                </div>
                
                <?php if (empty($plugins)): ?>
                    <p>No plugins installed.</p>
                <?php else: ?>
                    <ul class="plugin-list">
                        <?php foreach ($plugins as $plugin): ?>
                            <li class="plugin-item">
                                <div class="plugin-title"><?php echo htmlspecialchars($plugin['title'] ?? $plugin['name']); ?></div>
                                <div class="plugin-description"><?php echo htmlspecialchars($plugin['description'] ?? ''); ?></div>
                                <div class="plugin-info">
                                    Version: <?php echo htmlspecialchars($plugin['version'] ?? '1.0.0'); ?> | 
                                    Author: <?php echo htmlspecialchars($plugin['author'] ?? 'Unknown'); ?>
                                </div>
                                <form method="post" action="" style="margin-top: 10px;">
                                    <input type="hidden" name="plugin_name" value="<?php echo htmlspecialchars($plugin['name']); ?>">
                                    <button type="submit" name="uninstall_plugin">Uninstall</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="panel">
                <div class="panel-header">
                    <h2>Install New Plugin</h2>
                </div>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="plugin_data">Plugin Data (JSON):</label>
                        <textarea id="plugin_data" name="plugin_data"></textarea>
                    </div>
                    
                    <button type="submit" name="install_plugin">Install Plugin</button>
                </form>
                
                <h3>Sample Plugin Data:</h3>
                <pre><?php echo htmlspecialchars(json_encode($samplePlugin, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                const tabName = this.textContent.toLowerCase();
                document.querySelector(`.tab-content:nth-of-type(${Array.from(tabs).indexOf(this) + 1})`).classList.add('active');
            });
        });
    });
    </script>
</body>
</html> 