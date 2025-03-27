<?php
// This script adds a customer_id column to the orders table if it doesn't exist

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test";

// Set headers for plain text response
header('Content-Type: text/plain');

echo "Checking orders table structure...\n";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  echo "Connection failed: " . $conn->connect_error;
  exit;
}

// Check if orders table exists
$check_orders_table = $conn->query("SHOW TABLES LIKE 'orders'");
if ($check_orders_table->num_rows == 0) {
  echo "Error: orders table does not exist.";
  exit;
}

// Check if customer_id column exists
$check_customer_id = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_id'");
if ($check_customer_id->num_rows > 0) {
  echo "customer_id column already exists in orders table.";
} else {
  // Add customer_id column
  $alter_query = "ALTER TABLE orders ADD COLUMN customer_id INT NULL AFTER order_id";
  if ($conn->query($alter_query) === TRUE) {
    echo "customer_id column added successfully to orders table.";
  } else {
    echo "Error adding customer_id column: " . $conn->error;
  }
}

// Close connection
$conn->close();
?>

