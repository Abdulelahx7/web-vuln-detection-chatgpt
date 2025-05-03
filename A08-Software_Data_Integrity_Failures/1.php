<?php
$data = [];
$error = '';
$message = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $filename = $_POST['filename'] ?? 'data.txt';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($name) || empty($email)) {
            $error = "Name and email are required";
        } else {
            $data = [
                'name' => $name,
                'email' => $email,
                'notes' => $notes,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $serializedData = serialize($data);
            file_put_contents($filename, $serializedData);
            $message = "Data saved to $filename";
        }
    }
}

$sampleData = 'O:8:"stdClass":3:{s:4:"name";s:10:"John Smith";s:5:"email";s:15:"john@smith.com";s:5:"notes";s:20:"This is sample data.";}';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Serialization Demo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #333; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], textarea { width: 100%; padding: 8px; }
        textarea { height: 100px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; }
        .panel-header { background-color: #f5f5f5; padding: 10px; margin: -15px -15px 15px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Data Serialization Demo</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Save Data</h2>
            </div>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="filename">Filename:</label>
                    <input type="text" id="filename" name="filename" value="data.txt">
                </div>
                
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="save">Save Data</button>
            </form>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Load Data</h2>
            </div>
            
            <p>Load data from a file:</p>
            <a href="?load=data.txt">Load data.txt</a>
            
            <h3>Current Loaded Data:</h3>
            <?php if (!empty($data)): ?>
                <pre><?php print_r($data); ?></pre>
            <?php else: ?>
                <p>No data loaded.</p>
            <?php endif; ?>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Sample Serialized Data</h2>
            </div>
            
            <pre><?php echo htmlspecialchars($sampleData); ?></pre>
            <p>You can save this to a file and then load it.</p>
        </div>
    </div>
</body>
</html> 