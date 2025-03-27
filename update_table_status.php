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
    
    $table_number = isset($_POST['table_number']) ? intval($_POST['table_number']) : 0;
    $status = isset($_POST['status']) ? htmlspecialchars(trim($_POST['status'])) : '';
    

    if (empty($table_number) || empty($status)) {
        echo "<script>
            alert('Table number and status are required');
            window.history.back();
        </script>";
        exit;
    }
    
    
    $valid_statuses = ['Available', 'Occupied', 'Reserved', 'Locked'];
    if (!in_array($status, $valid_statuses)) {
        echo "<script>
            alert('Invalid status');
            window.history.back();
        </script>";
        exit;
    }
    
   
    $stmt = $conn->prepare("UPDATE restaurant_tables SET status = ? WHERE table_number = ?");
    $stmt->bind_param("si", $status, $table_number);
    $stmt->execute();
    
    
    if ($stmt->affected_rows == 0) {
        
        $stmt->close();
        $capacity = 4; y
        $table_name = "table_number " . $table_number; 
        
        $stmt = $conn->prepare("INSERT INTO restaurant_tables (table_number, table_name, capacity, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $table_number, $table_name, $capacity, $status);
        $stmt->execute();
        
        echo "<script>
            alert('New table created with status: " . $status . "');
            window.location.href = 'DETAILSENTRY.html';
        </script>";
    } else {
        echo "<script>
            alert('Table status updated to: " . $status . "');
            window.location.href = 'login/menu/NEGADMENU.html';
        </script>";
    }
    
   
    if ($status === 'Occupied') { 
        echo "<script> 
            window.location.href = 'menu/NEGADMENU.html?table=" . $table_number . "'; 
        </script>"; 
    } 
    
    $stmt->close();
} else {
    // Redirect to the form if accessed directly
    header("Location: menu/NEGADMENU.html");
    exit;
}

$conn->close();
?>