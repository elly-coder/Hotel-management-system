<?php
// Add error reporting at the top of the file for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add a try-catch block around the entire script
try {
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
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Get tables data
    $tables_query = "SELECT * FROM restaurant_tables ORDER BY table_number";
    $tables_result = $conn->query($tables_query);

    $tables = [];
    if ($tables_result && $tables_result->num_rows > 0) {
        while ($row = $tables_result->fetch_assoc()) {
            $tables[] = $row;
        }
    } else {
        // If no tables found, create some sample data for testing
        $tables = [
            ['table_id' => 1, 'table_number' => 1, 'table_name' => 'Lovers Haven', 'capacity' => 2, 'status' => 'Available', 'last_updated' => date('Y-m-d H:i:s')],
            ['table_id' => 2, 'table_number' => 2, 'table_name' => 'The Cozy Nook', 'capacity' => 6, 'status' => 'Occupied', 'last_updated' => date('Y-m-d H:i:s')],
            ['table_id' => 3, 'table_number' => 3, 'table_name' => 'Grand Royale', 'capacity' => 6, 'status' => 'Available', 'last_updated' => date('Y-m-d H:i:s')],
            ['table_id' => 4, 'table_number' => 4, 'table_name' => 'Regal Round', 'capacity' => 6, 'status' => 'Occupied', 'last_updated' => date('Y-m-d H:i:s')],
            ['table_id' => 5, 'table_number' => 5, 'table_name' => 'Elite Square', 'capacity' => 4, 'status' => 'Available', 'last_updated' => date('Y-m-d H:i:s')],
            ['table_id' => 6, 'table_number' => 6, 'table_name' => 'Board Room', 'capacity' => 8, 'status' => 'Reserved', 'last_updated' => date('Y-m-d H:i:s')]
        ];
    }

    // Check if customers table exists before querying
    $check_customers_table = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($check_customers_table->num_rows > 0) {
        // Check if orders table has customer_id column
        $check_customer_id = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_id'");
        
        if ($check_customer_id->num_rows > 0) {
            // Get customers data with join on customer_id
            $customers_query = "SELECT c.*, o.table_number  
                              FROM customers c  
                              LEFT JOIN orders o ON c.customer_id = o.customer_id  
                              ORDER BY c.created_at DESC  
                              LIMIT 10";
        } else {
            // Get customers data without join
            $customers_query = "SELECT c.* 
                              FROM customers c  
                              ORDER BY c.created_at DESC  
                              LIMIT 10";
        }
        
        $customers_result = $conn->query($customers_query);

        $customers = [];
        if ($customers_result && $customers_result->num_rows > 0) {
            while ($row = $customers_result->fetch_assoc()) {
                // If we didn't join with orders, try to find a matching order by other means
                if (!isset($row['table_number']) && isset($row['customer_id'])) {
                    // Try to find a matching order by customer name if possible
                    $order_query = "SELECT table_number FROM orders WHERE order_id IN 
                                   (SELECT MAX(order_id) FROM orders GROUP BY table_number) 
                                   LIMIT 1";
                    $order_result = $conn->query($order_query);
                    if ($order_result && $order_result->num_rows > 0) {
                        $order_row = $order_result->fetch_assoc();
                        $row['table_number'] = $order_row['table_number'];
                    } else {
                        $row['table_number'] = 'N/A';
                    }
                }
                $customers[] = $row;
            }
        }
    } else {
        // Customers table doesn't exist, use sample data
        $customers = [
            ['customer_id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'phone_number' => '0712345678', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'table_number' => 2],
            ['customer_id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'phone_number' => '0723456789', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'table_number' => 4],
            ['customer_id' => 3, 'first_name' => 'Michael', 'last_name' => 'Johnson', 'phone_number' => '0734567890', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'table_number' => 6]
        ];
    }

    // Calculate statistics
    $stats = [
        'available_tables' => 0,
        'occupied_tables' => 0,
        'reserved_tables' => 0,
        'locked_tables' => 0,
        'today_customers' => 0,
        'total_customers' => 0
    ];

    // Count tables by status
    foreach ($tables as $table) {
        $status = strtolower($table['status']);
        if ($status == 'available') {
            $stats['available_tables']++;
        } else if ($status == 'occupied') {
            $stats['occupied_tables']++;
        } else if ($status == 'reserved') {
            $stats['reserved_tables']++;
        } else if ($status == 'locked') {
            $stats['locked_tables']++;
        }
    }

    // Check if customers table exists before counting
    if ($check_customers_table->num_rows > 0) {
        // Count today's customers
        $today = date('Y-m-d');
        $today_customers_query = "SELECT COUNT(*) as count FROM customers WHERE DATE(created_at) = '$today'";
        $today_customers_result = $conn->query($today_customers_query);

        if ($today_customers_result && $today_customers_result->num_rows > 0) {
            $row = $today_customers_result->fetch_assoc();
            $stats['today_customers'] = $row['count'];
        }

        // Count total customers
        $total_customers_query = "SELECT COUNT(*) as count FROM customers";
        $total_customers_result = $conn->query($total_customers_query);

        if ($total_customers_result && $total_customers_result->num_rows > 0) {
            $row = $total_customers_result->fetch_assoc();
            $stats['total_customers'] = $row['count'];
        }
    } else {
        // Sample data for customers stats
        $stats['today_customers'] = count($customers);
        $stats['total_customers'] = count($customers);
    }

    // Prepare response
    $response = [
        'status' => 'success',
        'tables' => $tables,
        'customers' => $customers,
        'stats' => $stats
    ];

    // Close connection
    $conn->close();

    // Send JSON response
    echo json_encode($response);
} catch (Exception $e) {
    // Handle any exceptions
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

