<?php
$plugins = [
    [
        'id' => 1,
        'name' => 'Image Editor',
        'version' => '1.2.0',
        'update_url' => 'http://localhost/fake_update_server.php',
        'directory' => 'image_editor'
    ],
    [
        'id' => 2,
        'name' => 'Form Builder',
        'version' => '2.0.1',
        'update_url' => 'http://localhost/fake_update_server.php',
        'directory' => 'form_builder'
    ],
    [
        'id' => 3,
        'name' => 'SEO Tools',
        'version' => '1.0.5',
        'update_url' => 'http://localhost/fake_update_server.php',
        'directory' => 'seo_tools'
    ]
];

$message = '';
$updateData = null;

if (isset($_GET['check_updates'])) {
    $pluginId = $_GET['check_updates'];
    
    foreach ($plugins as $plugin) {
        if ($plugin['id'] == $pluginId) {
            $url = $plugin['update_url'] . "?plugin=" . $plugin['name'] . "&version=" . $plugin['version'];
            $updateInfo = file_get_contents($url);
            $updateData = json_decode($updateInfo, true);
            
            if ($updateData && version_compare($updateData['version'], $plugin['version'], '>')) {
                $message = "Update available for {$plugin['name']}: version {$updateData['version']}";
            } else {
                $message = "No updates available for {$plugin['name']}";
            }
            
            break;
        }
    }
}

if (isset($_GET['update'])) {
    $pluginId = $_GET['update'];
    
    foreach ($plugins as &$plugin) {
        if ($plugin['id'] == $pluginId) {
            $url = $plugin['update_url'] . "?plugin=" . $plugin['name'] . "&version=" . $plugin['version'];
            $updateInfo = file_get_contents($url);
            $updateData = json_decode($updateInfo, true);
            
            if ($updateData && version_compare($updateData['version'], $plugin['version'], '>')) {
                $downloadUrl = $updateData['download_url'];
                $pluginZip = @file_get_contents($downloadUrl);
                
                if ($pluginZip) {
                    $message = "Update downloaded for {$plugin['name']} (version {$updateData['version']})";
                    
                    $plugin['version'] = $updateData['version'];
                } else {
                    $message = "Error downloading update!";
                }
            } else {
                $message = "No updates available for {$plugin['name']}";
            }
            
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Plugin Manager</title>
</head>
<body>
    <h1>Plugin Manager</h1>
    
    <?php if ($message): ?>
    <div style="color: <?php echo strpos($message, 'Error') === 0 ? 'red' : 'green'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <table border="1">
        <tr>
            <th>Plugin Name</th>
            <th>Version</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($plugins as $plugin): ?>
        <tr>
            <td><?php echo $plugin['name']; ?></td>
            <td><?php echo $plugin['version']; ?></td>
            <td>
                <a href="?check_updates=<?php echo $plugin['id']; ?>">Check for Updates</a>
                |
                <a href="?update=<?php echo $plugin['id']; ?>">Update Now</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <?php if ($updateData): ?>
    <h2>Update Information</h2>
    <pre><?php print_r($updateData); ?></pre>
    <?php endif; ?>
</body>
</html>