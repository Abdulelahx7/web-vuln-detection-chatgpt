# A10: Server-Side Request Forgery (SSRF)

## Description
Server-Side Request Forgery (SSRF) occurs when an application fetches a remote resource without validating the user-supplied URL. This allows an attacker to coerce the application to send a crafted request to an unexpected destination, even when protected by a firewall, VPN, or another type of network access control list (ACL).

SSRF vulnerabilities can lead to:
- Access to internal services behind firewalls
- Access to metadata services in cloud environments (AWS, Azure, GCP)
- Access to local files using file:// URLs
- Port scanning internal networks
- Leakage of sensitive data
- In some cases, remote code execution

## Vulnerable Code Examples

### Easy Example: Direct URL Fetching

This code directly fetches a URL provided by a user:

```php
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    
    $content = file_get_contents($url);
    
    if ($content === FALSE) {
        $error = "Failed to fetch the URL: $url";
    }
}
```

### Hard Example: Indirect URL Processing via API Client

This code provides a more complex implementation that still allows SSRF through an API client:

```php
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

// Later used in code:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['get_metadata'])) {
        $server = $_POST['server'] ?? '';
        
        if (!empty($server)) {
            $metadata = $metadataService->getServerMetadata($server);
            // ...
        }
    } elseif (isset($_POST['import_image'])) {
        $imageUrl = $_POST['image_url'] ?? '';
        
        if (!empty($imageUrl)) {
            $image = $metadataService->importExternalImage($imageUrl);
            // ...
        }
    }
}
```

## Proof of Concept (PoC)

### Easy Example PoC
1. Access the URL fetcher at `easy_vulnerable.php`
2. Instead of a normal URL, enter `file:///etc/passwd` to access local files on the server
3. Or enter `http://localhost:8080` to access internal services
4. In cloud environments, try `http://169.254.169.254/latest/meta-data/` (AWS) or equivalent metadata endpoints

### Hard Example PoC
1. Access the system administration panel at `hard_vulnerable.php`
2. Try accessing the server metadata section and enter:
   - `localhost:22` - to scan for SSH
   - `internal-service.local` - to access internal domains
   - `169.254.169.254` - to access cloud metadata service
3. Alternatively, in the image import tool, enter:
   - `file:///etc/passwd` - to access local files
   - `http://internal-api:8080/config` - to access internal APIs

## Security Fix Recommendations

### For Easy Example:
```php
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    
    // 1. Validate URL scheme
    $parsedUrl = parse_url($url);
    if (!$parsedUrl || !isset($parsedUrl['scheme']) || 
        !in_array($parsedUrl['scheme'], ['http', 'https'])) {
        $error = "Invalid URL scheme. Only HTTP and HTTPS are allowed.";
    }
    // 2. Validate hostname - block private IPs and localhost
    else if (isset($parsedUrl['host'])) {
        $hostname = $parsedUrl['host'];
        
        // Block localhost and variants
        if (preg_match('/^(localhost|127\.|::1)/', $hostname)) {
            $error = "Localhost addresses are not allowed.";
        }
        // Check if it's an IP address
        else if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            // Block private IP ranges
            if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $error = "Private or reserved IP addresses are not allowed.";
            }
        } 
        // 3. Use an allowlist of domains if possible
        else if (!preg_match('/\.(example\.com|trusted-domain\.org)$/', $hostname)) {
            $error = "Domain not in allowed list.";
        }
        else {
            // 4. Use a proper HTTP client with timeouts and limits
            $client = new GuzzleHttp\Client([
                'timeout' => 5,
                'allow_redirects' => ['max' => 2], // Limit redirects
                'headers' => [
                    'User-Agent' => 'MyApp/1.0'
                ]
            ]);
            
            try {
                $response = $client->get($url);
                $content = (string) $response->getBody();
            } catch (Exception $e) {
                $error = "Failed to fetch URL: " . $e->getMessage();
            }
        }
    } else {
        $error = "Invalid URL format.";
    }
}
```

### For Hard Example:
```php
public function getServerMetadata($server) {
    // 1. Use an explicit allowlist of permitted servers
    $allowedServers = [
        'prod-server-1',
        'prod-server-2',
        'stage-server-1'
    ];
    
    if (!in_array($server, $allowedServers, true)) {
        throw new Exception("Server not in allowed list");
    }
    
    // 2. Use a predefined mapping instead of dynamic URL construction
    $serverEndpoints = [
        'prod-server-1' => 'https://prod-server-1.internal.company.com/metadata',
        'prod-server-2' => 'https://prod-server-2.internal.company.com/metadata',
        'stage-server-1' => 'https://stage-1.internal.company.com/metadata'
    ];
    
    $metadataUrl = $serverEndpoints[$server];
    
    // 3. Add a timeout and error handling
    try {
        return $this->apiClient->fetchExternalResource($metadataUrl);
    } catch (Exception $e) {
        $this->logger->error("Failed to fetch metadata for server {$server}: {$e->getMessage()}");
        return null;
    }
}

public function importExternalImage($imageUrl) {
    // 1. Validate URL scheme
    $parsedUrl = parse_url($imageUrl);
    if (!$parsedUrl || !isset($parsedUrl['scheme']) || 
        !in_array($parsedUrl['scheme'], ['http', 'https'])) {
        throw new Exception("Invalid URL scheme. Only HTTP and HTTPS are allowed.");
    }
    
    // 2. Validate hostname against blocklist and allowlist
    $hostname = $parsedUrl['host'] ?? '';
    
    // Block localhost and private IPs
    if (preg_match('/^(localhost|127\.|::1)/', $hostname) || $this->isPrivateIP($hostname)) {
        throw new Exception("Internal addresses are not allowed.");
    }
    
    // 3. Use a proper HTTP client with timeouts
    $client = new GuzzleHttp\Client([
        'timeout' => 10,
        'allow_redirects' => ['max' => 3],
    ]);
    
    // 4. Validate content type after fetching
    try {
        $response = $client->head($imageUrl);
        $contentType = $response->getHeaderLine('Content-Type');
        
        if (!preg_match('/^image\/(jpeg|png|gif)/', $contentType)) {
            throw new Exception("URL does not point to a valid image resource.");
        }
        
        // 5. Now fetch the actual image
        $response = $client->get($imageUrl);
        return (string) $response->getBody();
    } catch (Exception $e) {
        $this->logger->error("Image import failed: {$e->getMessage()}");
        throw new Exception("Image import failed: {$e->getMessage()}");
    }
}

// Helper function to check if a hostname resolves to a private IP
private function isPrivateIP($hostname) {
    $ip = gethostbyname($hostname);
    
    // Return true if we couldn't resolve the hostname or if it's a private IP
    return $ip === $hostname || 
           filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}
```

## Additional Security Measures

1. **Network Segmentation**: Implement proper network segmentation to limit the impact of SSRF
2. **Access Controls**: Use access controls at the network level to prevent the application server from accessing private networks
3. **Metadata Protection**: In cloud environments, disable metadata service access from instances that don't need it
4. **Web Application Firewall**: Configure WAF rules to detect and block SSRF attempts
5. **Deny by Default**: Use deny-by-default policies for all outbound connections
6. **URL Hardening**: For functionality requiring URL parameters, use signed URLs or URL tokens
7. **Response Handling**: Be careful not to return raw responses to users, which might leak sensitive information 