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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    // Validate required data
    if (!isset($data['order_id']) || !isset($data['status'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
        exit;
    }
    
    // Extract data
    $order_id = intval($data['order_id']);
    $status = $data['status'];
    
    // Validate status
    $valid_statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit;
    }
    
    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    
    // Check if the order was found and updated
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Order status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    }
    
    $stmt->close();
} else {
    
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>