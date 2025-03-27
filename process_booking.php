<?php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "test";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $first_name = isset($_POST['first_name']) ? htmlspecialchars(trim($_POST['first_name'])) : '';
    $last_name = isset($_POST['last_name']) ? htmlspecialchars(trim($_POST['last_name'])) : '';
    $phone_number = isset($_POST['phone_number']) ? htmlspecialchars(trim($_POST['phone_number'])) : '';
    $table_number = isset($_POST['table_number']) ? intval($_POST['table_number']) : 0;
    $table_name = isset($_POST['table_name']) ? htmlspecialchars(trim($_POST['table_name'])) : '';
    $table_status = isset($_POST['table_status']) ? htmlspecialchars(trim($_POST['table_status'])) : 'Reserved';
    
   
    if (empty($first_name) || empty($last_name) || empty($phone_number) || empty($table_number)) {
        echo "<script>
            alert('All required fields must be filled');
            window.history.back();
        </script>";
        exit;
    }
    
    
    if (!preg_match('/^(07|01)\d{8}$/', $phone_number)) {
        echo "<script>
            alert('Invalid phone number format');
            window.history.back();
        </script>";
        exit;
    }
    
   
    $conn->begin_transaction();
    
    try {
        // inserting into customers table
        $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, phone_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $first_name, $last_name, $phone_number);
        $stmt->execute();
        $customer_id = $conn->insert_id; // Get the ID of the newly inserted customer
        $stmt->close();
        
        
        $stmt = $conn->prepare("SELECT table_id FROM restaurant_tables WHERE table_number = ?");
        $stmt->bind_param("i", $table_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
           
            $stmt->close();
            $stmt = $conn->prepare("UPDATE restaurant_tables SET status = ?, table_name = ? WHERE table_number = ?");
            $stmt->bind_param("ssi", $table_status, $table_name, $table_number);
            $stmt->execute();
        } else {
         
            $stmt->close();
            $capacity = 4; 
            $stmt = $conn->prepare("INSERT INTO restaurant_tables (table_number, table_name, capacity, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $table_number, $table_name, $capacity, $table_status);
            $stmt->execute();
        }
        
        $stmt->close();
        
       
        $conn->commit();
        
       
        echo "<script>
            window.history.back();
        </script>";
    } catch (Exception $e) {
        
        $conn->rollback();
        echo "<script>
            alert('Error: " . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
} else {
    
    header("Location: customer_form.html");
    exit;
}

$conn->close();
?>