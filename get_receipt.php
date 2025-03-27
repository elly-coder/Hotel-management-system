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
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Set headers for JSON response
header('Content-Type: application/json');

// Check if order ID is specified
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode(['error' => 'Order ID not specified']);
    exit;
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
    echo json_encode(['error' => 'Order not found']);
    exit;
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
    $items[] = [
        'item_name' => $item['item_name'],
        'quantity' => (int)$item['quantity'],
        'price' => (float)$item['price'],
        'total' => (float)$item['total']
    ];
    
    $subtotal += (float)$item['total'];
}

// Calculate tax (assuming 16% VAT)
$tax = $subtotal * 0.16;

// Prepare response
$response = [
    'order_id' => (int)$orderData['order_id'],
    'table_number' => (int)$orderData['table_number'],
    'date' => $orderData['date'],
    'items' => $items,
    'subtotal' => $subtotal,
    'tax' => $tax,
    'total_amount' => (float)$orderData['total_amount']
];

// Return response
echo json_encode($response);

$stmt->close();
$conn->close();
?>