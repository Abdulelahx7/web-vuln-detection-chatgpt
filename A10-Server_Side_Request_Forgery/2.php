<?php
class APIClient {
    private $baseUrl;
    private $apiKey;
    
    public function __construct($baseUrl, $apiKey) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }
    
    public function fetchResource($endpoint, $params = []) {
        $url = $this->baseUrl . '/' . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    public function fetchExternalResource($resource) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $resource);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}

class MetaDataService {
    private $apiClient;
    
    public function __construct(APIClient $apiClient) {
        $this->apiClient = $apiClient;
    }
    
    public function getServerMetadata($server) {
        if (filter_var($server, FILTER_VALIDATE_IP) || preg_match('/^[\w\.-]+$/', $server)) {
            $metadataUrl = "http://$server/metadata";
            return $this->apiClient->fetchExternalResource($metadataUrl);
        }
        
        return null;
    }
    
    public function importExternalImage($imageUrl) {
        return $this->apiClient->fetchExternalResource($imageUrl);
    }
    
    public function getWeatherData($city) {
        return $this->apiClient->fetchResource('weather', ['city' => $city]);
    }
}

$apiClient = new APIClient('https://api.example.com', 'your-api-key-here');
$metadataService = new MetaDataService($apiClient);

$error = '';
$result = '';
$resultType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['get_weather'])) {
        $city = $_POST['city'] ?? '';
        
        if (!empty($city)) {
            $weatherData = $metadataService->getWeatherData($city);
            
            if ($weatherData) {
                $result = $weatherData;
                $resultType = 'weather';
            } else {
                $error = "Failed to fetch weather data for $city";
            }
        } else {
            $error = "City is required";
        }
    } elseif (isset($_POST['get_metadata'])) {
        $server = $_POST['server'] ?? '';
        
        if (!empty($server)) {
            $metadata = $metadataService->getServerMetadata($server);
            
            if ($metadata) {
                $result = $metadata;
                $resultType = 'metadata';
            } else {
                $error = "Failed to fetch metadata for server $server";
            }
        } else {
            $error = "Server is required";
        }
    } elseif (isset($_POST['import_image'])) {
        $imageUrl = $_POST['image_url'] ?? '';
        
        if (!empty($imageUrl)) {
            $image = $metadataService->importExternalImage($imageUrl);
            
            if ($image) {
                $result = "Image imported successfully (length: " . strlen($image) . " bytes)";
                $resultType = 'image';
            } else {
                $error = "Failed to import image from $imageUrl";
            }
        } else {
            $error = "Image URL is required";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>System Administration Tools</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1, h2 { color: #333; }
        .error { color: red; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"] { width: 100%; padding: 8px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; }
        .panel-header { background-color: #f5f5f5; padding: 10px; margin: -15px -15px 15px; border-bottom: 1px solid #ddd; }
        .result { border: 1px solid #ddd; padding: 15px; margin-top: 20px; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Administration Tools</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Weather Service</h2>
            </div>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="city">City Name:</label>
                    <input type="text" id="city" name="city" required>
                </div>
                
                <button type="submit" name="get_weather">Get Weather</button>
            </form>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Server Metadata</h2>
            </div>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="server">Server Name or IP:</label>
                    <input type="text" id="server" name="server" required>
                </div>
                
                <button type="submit" name="get_metadata">Get Metadata</button>
            </form>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Image Import Tool</h2>
            </div>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="image_url">Image URL:</label>
                    <input type="text" id="image_url" name="image_url" required>
                </div>
                
                <button type="submit" name="import_image">Import Image</button>
            </form>
        </div>
        
        <?php if ($result): ?>
            <div class="result">
                <h2>Result:</h2>
                <?php if ($resultType === 'weather' || $resultType === 'metadata'): ?>
                    <pre><?php echo htmlspecialchars(print_r($result, true)); ?></pre>
                <?php else: ?>
                    <p><?php echo htmlspecialchars($result); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 