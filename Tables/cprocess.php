<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test";

// Create 
$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $table_number = $_POST['table_number'];
    $table_name = $_POST['table_name'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'Available';
    
    //  validation
    if (empty($first_name) || empty($last_name) || empty($phone_number)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are empty']);
        exit;
    }
    
   
    if (!preg_match('/^(07|01)\d{8}$/', $phone_number)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
        exit;
    }
    
  
    $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, phone_number) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $first_name, $last_name, $phone_number);
    
    if ($stmt->execute()) {
        $customer_id = $conn->insert_id;
        
       
        if (!empty($table_number)) {
            //  table exists
            $check_table = $conn->prepare("SELECT table_id FROM restaurant_tables WHERE table_number = ?");
            $check_table->bind_param("i", $table_number);
            $check_table->execute();
            $result = $check_table->get_result();
            
            /* if ($result->num_rows > 0) {
                // Update existing table
                $update_table = $conn->prepare("UPDATE restaurant_tables SET status = ?, table_name = ? WHERE table_number = ?");
                $update_table->bind_param("ssi", $status, $table_name, $table_number);
                $update_table->execute(); */
            } else {
                // Insert new table
                $insert_table = $conn->prepare("INSERT INTO restaurant_tables (table_number, table_name, capacity, status) VALUES (?, ?, 4, ?)");
                $insert_table->bind_param("iss", $table_number, $table_name, $status);
                $insert_table->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'Customer and table information saved successfully']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Customer information saved successfully']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error saving customer information']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}
?>