<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    

    if (!isset($data['table_number']) || !isset($data['items']) || empty($data['items'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
        exit;
    }
    
    
    $table_number = intval($data['table_number']);
    $items = $data['items'];
    $total_amount = floatval($data['total_amount']);
    
 
    $conn->begin_transaction();
    
    try {
        
        $stmt = $conn->prepare("INSERT INTO orders (table_number, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $table_number, $total_amount);
        $stmt->execute();
        
  
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Insert each item into order_items table
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, item_name, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {

            $item_query = $conn->prepare("SELECT item_id FROM menu_items WHERE name = ? LIMIT 1");
            $item_query->bind_param("s", $item['name']);
            $item_query->execute();
            $result = $item_query->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $item_id = $row['item_id'];
            } else {
      
                $item_id = 0;
            }
            $item_query->close();
            
      
            $stmt->bind_param("iisidi", 
                $order_id, 
                $item_id, 
                $item['name'], 
                $item['quantity'], 
                $item['price'], 
                $item['total']
            );
            $stmt->execute();
        }
        
        $stmt->close();
        

        $conn->commit();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Order placed successfully', 
            'order_id' => $order_id
        ]);
        
    } catch (Exception $e) {
        
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {

    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>