<?php
$message = '';
$preview = [];

function isValidUrl($url) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $parsedUrl = parse_url($url);
        
        $hostname = $parsedUrl['host'];
        
        if (preg_match('/^(localhost|127\.0\.0\.1)$/i', $hostname)) {
            return false;
        }
        
        if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            $ip = ip2long($hostname);
            
            $privateRanges = [
                ['10.0.0.0', '10.255.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['127.0.0.0', '127.255.255.255']
            ];
            
            foreach ($privateRanges as $range) {
                $min = ip2long($range[0]);
                $max = ip2long($range[1]);
                
                if ($ip >= $min && $ip <= $max) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentContent = $_POST['document_content'];
    
    $dom = new DOMDocument();
    $dom->loadHTML($documentContent);
    
    $images = $dom->getElementsByTagName('img');
    
    foreach ($images as $image) {
        $src = $image->getAttribute('src');
        
        if (filter_var($src, FILTER_VALIDATE_URL)) {
            if (isValidUrl($src)) {
                try {
                    $imageData = @file_get_contents($src);
                    $imageType = exif_imagetype($src);
                    
                    if ($imageData && $imageType) {
                        $preview[] = [
                            'url' => $src,
                            'type' => image_type_to_mime_type($imageType),
                            'size' => strlen($imageData)
                        ];
                    } else {
                        $message = "Error processing image: $src";
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                }
            } else {
                $preview[] = [
                    'url' => $src,
                    'type' => 'Invalid URL (blocked)',
                    'size' => 0
                ];
            }
        }
    }
    
    if (empty($preview) && empty($message)) {
        $message = "No images found in document";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Document Image Preview</title>
</head>
<body>
    <h1>Document Image Preview Generator</h1>
    
    <?php if ($message): ?>
    <div style="color: <?php echo strpos($message, 'Error') === 0 ? 'red' : 'blue'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label>Paste HTML Document Content:<br>
                <textarea name="document_content" rows="10" cols="80" required><?php echo isset($_POST['document_content']) ? htmlspecialchars($_POST['document_content']) : ''; ?></textarea>
            </label>
        </div>
        <div>
            <input type="submit" value="Process Images">
        </div>
    </form>
    
    <?php if (!empty($preview)): ?>
    <h2>Image Preview Results</h2>
    <table border="1">
        <tr>
            <th>Image URL</th>
            <th>Type</th>
            <th>Size</th>
        </tr>
        <?php foreach ($preview as $image): ?>
        <tr>
            <td><?php echo htmlspecialchars($image['url']); ?></td>
            <td><?php echo htmlspecialchars($image['type']); ?></td>
            <td><?php echo $image['size']; ?> bytes</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    
    <div>
        <h3>Sample Document with Images:</h3>
        <pre>
&lt;html&gt;
&lt;body&gt;
    &lt;h1&gt;My Document&lt;/h1&gt;
    &lt;img src="https://example.com/image.jpg" /&gt;
    &lt;img src="https://via.placeholder.com/150" /&gt;
&lt;/body&gt;
&lt;/html&gt;
        </pre>
    </div>
</body>
</html>