<?php
session_start();

$error = '';
$content = '';

if (isset($_GET['url'])) {
    $url = $_GET['url'];
    
    $content = file_get_contents($url);
    
    if ($content === FALSE) {
        $error = "Failed to fetch the URL: $url";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>URL Fetcher</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .error { color: red; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"] { width: 100%; padding: 8px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .result { border: 1px solid #ddd; padding: 15px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>URL Fetcher</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="get" action="">
            <div class="form-group">
                <label for="url">Enter URL to fetch:</label>
                <input type="text" id="url" name="url" value="<?php echo isset($_GET['url']) ? htmlspecialchars($_GET['url']) : ''; ?>" required>
            </div>
            
            <button type="submit">Fetch URL</button>
        </form>
        
        <?php if ($content): ?>
            <div class="result">
                <h2>Result:</h2>
                <pre><?php echo htmlspecialchars($content); ?></pre>
            </div>
        <?php endif; ?>
        
        <div>
            <h3>Example URLs:</h3>
            <ul>
                <li><a href="?url=https://example.com">https://example.com</a></li>
                <li><a href="?url=https://www.google.com">https://www.google.com</a></li>
            </ul>
        </div>
    </div>
</body>
</html>