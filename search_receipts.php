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

// Initialize query parameters
$params = [];
$types = "";
$whereConditions = [];

// Build query based on search parameters
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $whereConditions[] = "o.created_at >= ?";
    $params[] = $_GET['date_from'] . " 00:00:00";
    $types .= "s";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $whereConditions[] = "o.created_at <= ?";
    $params[] = $_GET['date_to'] . " 23:59:59";
    $types .= "s";
}

if (isset($_GET['table_number']) && !empty($_GET['table_number'])) {
    $whereConditions[] = "o.table_number = ?";
    $params[] = (int)$_GET['table_number'];
    $types .= "i";
}

if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $whereConditions[] = "o.order_id = ?";
    $params[] = (int)$_GET['order_id'];
    $types .= "i";
}

// Construct WHERE clause
$whereClause = "";
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// Query to get receipts
$query = "
    SELECT 
        o.order_id,
        o.table_number,
        DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i') AS date,
        o.total_amount,
        COUNT(oi.item_id) AS item_count
    FROM 
        orders o
    LEFT JOIN 
        order_items oi ON o.order_id = oi.order_id
    $whereClause
    GROUP BY 
        o.order_id
    ORDER BY 
        o.created_at DESC
    LIMIT 50
";

$stmt = $conn->prepare($query);

// Bind parameters if any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Prepare data for response
$receipts = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $receipts[] = [
            'order_id' => (int)$row['order_id'],
            'table_number' => (int)$row['table_number'],
            'date' => $row['date'],
            'total_amount' => (float)$row['total_amount'],
            'item_count' => (int)$row['item_count']
        ];
    }
}

// Return response
echo json_encode($receipts);

$stmt->close();
$conn->close();
?>