<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        [
            'id' => 1,
            'name' => 'Premium Headphones',
            'price' => 200.00,
            'quantity' => 1,
            'percentageDiscount' => 0,
            'fixedDiscount' => 0,
            'eligibleForLoyaltyDiscount' => true
        ]
    ];
}

$message = '';

if (isset($_POST['apply_discount'])) {
    $itemId = $_POST['item_id'];
    $discountType = $_POST['discount_type'];
    $discountValue = floatval($_POST['discount_value']);
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $itemId) {
            if ($discountType === 'percentage') {
                $item['percentageDiscount'] = $discountValue;
                $item['fixedDiscount'] = 0;
            } else {
                $item['fixedDiscount'] = $discountValue;
                $item['percentageDiscount'] = 0;
            }
            break;
        }
    }
    
    $message = "Discount applied successfully!";
}

if (isset($_POST['apply_loyalty'])) {
    $_SESSION['loyalty_applied'] = true;
    $message = "Loyalty discount applied!";
}

if (isset($_POST['reset'])) {
    $_SESSION['cart'] = [
        [
            'id' => 1,
            'name' => 'Premium Headphones',
            'price' => 200.00,
            'quantity' => 1,
            'percentageDiscount' => 0,
            'fixedDiscount' => 0,
            'eligibleForLoyaltyDiscount' => true
        ]
    ];
    unset($_SESSION['loyalty_applied']);
    $message = "Cart reset to default!";
}

function applyDiscounts($cart, $loyaltyApplied = false) {
    $finalPrice = 0;
    $itemPrices = [];
    
    foreach ($cart as $item) {
        $price = $item['price'];
        
        if ($item['percentageDiscount'] > 0) {
            $price = $price * (1 - $item['percentageDiscount'] / 100);
        }
        
        if ($item['fixedDiscount'] > 0) {
            $price = $price - $item['fixedDiscount'];
        }
        
        if ($loyaltyApplied && $item['eligibleForLoyaltyDiscount']) {
            $price = $price * 0.95;
        }
        
        $itemPrice = $price * $item['quantity'];
        $itemPrices[$item['id']] = $itemPrice;
        $finalPrice += $itemPrice;
    }
    
    return [
        'finalPrice' => $finalPrice,
        'itemPrices' => $itemPrices
    ];
}

$priceInfo = applyDiscounts($_SESSION['cart'], isset($_SESSION['loyalty_applied']));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
    <style>
        .negative { color: red; }
    </style>
</head>
<body>
    <h1>Shopping Cart</h1>
    
    <?php if ($message): ?>
    <div style="color: green;">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="submit" name="reset" value="Reset Cart">
    </form>
    
    <table border="1">
        <tr>
            <th>Product</th>
            <th>Base Price</th>
            <th>Quantity</th>
            <th>Percentage Discount</th>
            <th>Fixed Discount</th>
            <th>Loyalty Eligible</th>
            <th>Final Price</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($_SESSION['cart'] as $item): ?>
        <tr>
            <td><?php echo $item['name']; ?></td>
            <td>$<?php echo number_format($item['price'], 2); ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td><?php echo $item['percentageDiscount']; ?>%</td>
            <td>$<?php echo number_format($item['fixedDiscount'], 2); ?></td>
            <td><?php echo $item['eligibleForLoyaltyDiscount'] ? 'Yes' : 'No'; ?></td>
            <td class="<?php echo $priceInfo['itemPrices'][$item['id']] < 0 ? 'negative' : ''; ?>">
                $<?php echo number_format($priceInfo['itemPrices'][$item['id']], 2); ?>
            </td>
            <td>
                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                    <select name="discount_type">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                    <input type="number" name="discount_value" placeholder="Discount value" required>
                    <input type="submit" name="apply_discount" value="Apply">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>Total: <span class="<?php echo $priceInfo['finalPrice'] < 0 ? 'negative' : ''; ?>">
        $<?php echo number_format($priceInfo['finalPrice'], 2); ?>
    </span></h2>
    
    <form method="POST">
        <input type="submit" name="apply_loyalty" value="Apply Loyalty Discount (5%)">
    </form>
</body>
</html>