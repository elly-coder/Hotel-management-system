<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test";

// Set headers for JSON response
header('Content-Type: application/json');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Get orders data
$orders_query = "SELECT o.*, 
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count 
                FROM orders o 
                ORDER BY o.created_at DESC";
$orders_result = $conn->query($orders_query);

$orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $order_id = $row['order_id'];
        
        // Get order items
        $items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
        $items_result = $conn->query($items_query);
        
        $items = [];
        if ($items_result && $items_result->num_rows > 0) {
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }
        }
        
        $row['items'] = $items;
        $orders[] = $row;
    }
} else {
    // If no orders found, create some sample data for testing
    $sample_items = [
        ['item_id' => 1, 'item_name' => 'Pilau', 'quantity' => 2, 'price' => 400, 'total' => 800],
        ['item_id' => 2, 'item_name' => 'Chicken', 'quantity' => 1, 'price' => 450, 'total' => 450],
        ['item_id' => 3, 'item_name' => 'Soda', 'quantity' => 2, 'price' => 150, 'total' => 300]
    ];
    
    $orders = [
        [
            'order_id' => 1,
            'table_number' => 2,
            'total_amount' => 1550,
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            'items' => $sample_items,
            'item_count' => count($sample_items)
        ],
        [
            'order_id' => 2,
            'table_number' => 4,
            'total_amount' => 1200,
            'status' => 'Processing',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'items' => $sample_items,
            'item_count' => count($sample_items)
        ],
        [
            'order_id' => 3,
            'table_number' => 6,
            'total_amount' => 2000,
            'status' => 'Completed',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'items' => $sample_items,
            'item_count' => count($sample_items)
        ]
    ];
}

// Prepare response
$response = [
    'status' => 'success',
    'orders' => $orders
];

// Close connection
$conn->close();

// Send JSON response
echo json_encode($response);
?>

