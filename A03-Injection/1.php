<?php
$host = 'localhost';
$username = 'db_user';
$password = 'db_password';
$database = 'products_db';

$search = '';
$results = [];

if (isset($_GET['search'])) {
    $search = $_GET['search'];
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $query = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%'";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Search</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        tr:hover { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Product Search</h1>
    
    <form method="get" action="">
        <div>
            <label for="search">Search term:</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </div>
    </form>
    
    <?php if (!empty($results)): ?>
        <h2>Search Results</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td>$<?php echo htmlspecialchars($product['price']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($search): ?>
        <p>No products found matching '<?php echo htmlspecialchars($search); ?>'.</p>
    <?php endif; ?>
    
    <div>
        <h3>Example Searches:</h3>
        <ul>
            <li><a href="?search=laptop">laptop</a></li>
            <li><a href="?search=phone">phone</a></li>
        </ul>
    </div>
</body>
</html> 