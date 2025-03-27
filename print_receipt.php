<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if order ID is specified
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Order ID not specified");
}

$orderId = (int)$_GET['order_id'];

// Get order details
$orderQuery = "
    SELECT 
        o.order_id,
        o.table_number,
        o.total_amount,
        DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i') AS date
    FROM 
        orders o
    WHERE 
        o.order_id = ?
";

$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    die("Order not found");
}

$orderData = $orderResult->fetch_assoc();
$stmt->close();

// Get order items
$itemsQuery = "
    SELECT 
        oi.item_name,
        oi.quantity,
        oi.price,
        oi.total
    FROM 
        order_items oi
    WHERE 
        oi.order_id = ?
    ORDER BY 
        oi.item_id
";

$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$itemsResult = $stmt->get_result();

$items = [];
$subtotal = 0;

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
    $subtotal += (float)$item['total'];
}

// Calculate tax (assuming 16% VAT)
$tax = $subtotal * 0.16;

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $orderData['order_id']; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .receipt-header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .receipt-header p {
            font-size: 12px;
            margin: 2px 0;
        }
        
        .receipt-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .receipt-items th, .receipt-items td {
            padding: 5px;
            text-align: left;
            border-bottom: 1px dashed #ddd;
            font-size: 12px;
        }
        
        .receipt-total {
            text-align: right;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }
        
        .receipt-total p {
            margin: 3px 0;
        }
        
        .receipt-total .grand-total {
            font-weight: bold;
            font-size: 16px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
        }
        
        @media print {
            body {
                width: 80mm;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <h1>NEGAD GRILL</h1>
        <p>Magunas Road, Murang'a</p>
        <p>Tel: +254 123 456 789</p>
        <p>Receipt #<?php echo $orderData['order_id']; ?></p>
        <p>Date: <?php echo $orderData['date']; ?></p>
        <p>Table: <?php echo $orderData['table_number']; ?></p>
    </div>
    
    <table class="receipt-items">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>Ksh <?php echo number_format($item['price'], 2); ?></td>
                <td>Ksh <?php echo number_format($item['total'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="receipt-total">
        <p>Subtotal: Ksh <?php echo number_format($subtotal, 2); ?></p>
        <p>Tax (16%): Ksh <?php echo number_format($tax, 2); ?></p>
        <p class="grand-total">TOTAL: Ksh <?php echo number_format($orderData['total_amount'], 2); ?></p>
    </div>
    
    <div class="receipt-footer">
        <p>Thank you for dining with us!</p>
        <p>Please come again</p>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>