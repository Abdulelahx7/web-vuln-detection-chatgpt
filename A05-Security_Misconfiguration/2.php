<?php
session_start();

define('APP_ROOT', dirname(__FILE__));
define('APP_ENV', 'development');
define('DEBUG', false);

require_once 'config.php';  // This file doesn't exist but would contain configuration

class Logger {
    private $logFile;
    
    public function __construct($logFile = 'app.log') {
        $this->logFile = APP_ROOT . '/' . $logFile;
    }
    
    public function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}

class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function authenticate($username, $password) {
        $query = "SELECT * FROM users WHERE username = ? AND password = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $username, md5($password));
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}

class FileManager {
    private $uploadDirectory;
    
    public function __construct($uploadDirectory = 'uploads') {
        $this->uploadDirectory = APP_ROOT . '/' . $uploadDirectory;
        
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0777, true);
        }
    }
    
    public function uploadFile($file) {
        $fileName = basename($file['name']);
        $targetPath = $this->uploadDirectory . '/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
        
        return false;
    }
    
    public function getFile($fileName) {
        $filePath = $this->uploadDirectory . '/' . $fileName;
        
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        
        return null;
    }
}

function getRemoteData($url, $apiKey = null) {
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n" . 
                        ($apiKey ? "Authorization: Bearer $apiKey\r\n" : ""),
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result;
}

$logger = new Logger();
$fileManager = new FileManager();

$error = '';
$message = '';
$systemStatus = [];

if (isset($_GET['check_status'])) {
    $logger->log("Status check initiated");
    
    $memoryLimit = ini_get('memory_limit');
    $maxExecutionTime = ini_get('max_execution_time');
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    
    $systemStatus = [
        'PHP Version' => PHP_VERSION,
        'Memory Limit' => $memoryLimit,
        'Max Execution Time' => $maxExecutionTime,
        'Upload Max Filesize' => $uploadMaxFilesize,
        'Post Max Size' => $postMaxSize,
        'Server Software' => $_SERVER['SERVER_SOFTWARE'],
        'Document Root' => $_SERVER['DOCUMENT_ROOT'],
        'Extensions' => implode(', ', get_loaded_extensions())
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_file'])) {
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $fileName = $fileManager->uploadFile($_FILES['file']);
            
            if ($fileName) {
                $logger->log("File uploaded: $fileName");
                $message = "File uploaded successfully: $fileName";
            } else {
                $logger->log("File upload failed", "ERROR");
                $error = "Failed to upload file.";
            }
        } else {
            $logger->log("No file selected or upload error", "ERROR");
            $error = "Please select a file to upload.";
        }
    }
}

$files = glob(APP_ROOT . '/uploads/*.{jpg,jpeg,png,gif,txt,pdf}', GLOB_BRACE);
$fileList = [];

foreach ($files as $file) {
    $fileList[] = basename($file);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Administration Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1, h2 { color: #333; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; }
        .panel-header { background-color: #f5f5f5; padding: 10px; margin: -15px -15px 15px; border-bottom: 1px solid #ddd; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="file"] { margin-bottom: 10px; }
        button { padding: 8px 12px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Administration Panel</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="panel">
            <div class="panel-header">
                <h2>File Upload</h2>
            </div>
            
            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="file">Select file to upload:</label>
                    <input type="file" id="file" name="file" required>
                </div>
                <button type="submit" name="upload_file">Upload</button>
            </form>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Uploaded Files</h2>
            </div>
            
            <?php if (!empty($fileList)): ?>
                <ul>
                    <?php foreach ($fileList as $file): ?>
                        <li>
                            <?php echo htmlspecialchars($file); ?>
                            <a href="uploads/<?php echo urlencode($file); ?>" target="_blank">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No files uploaded yet.</p>
            <?php endif; ?>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>System Status</h2>
            </div>
            
            <p><a href="?check_status=1">Check System Status</a></p>
            
            <?php if (!empty($systemStatus)): ?>
                <table>
                    <tr>
                        <th>Property</th>
                        <th>Value</th>
                    </tr>
                    <?php foreach ($systemStatus as $property => $value): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($property); ?></td>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Development Mode Only: Remote Debug Console -->
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
</body>
</html> 