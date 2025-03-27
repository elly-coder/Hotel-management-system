<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Db conn
    $conn = new mysqli('localhost', 'root', '', 'test');
    if($conn->connect_error) {
        echo "$conn->connect_error";
        die("Connection Failed : ". $conn->connect_error);
    } else {
        // Checking kama user  in db or exists
        $stmt = $conn->prepare("SELECT * FROM registration WHERE email = ? AND password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            
            $user = $result->fetch_assoc();
            echo "Login successful! Welcome, " . $user['firstName'] . "!";
            
          
            header("Location: dashboard.html");
            exit();
        } else {
            
            echo "Invalid email or password. Please try again.";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>