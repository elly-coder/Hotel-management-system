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

// Check if report type is specified
if (!isset($_GET['type'])) {
    echo json_encode(['error' => 'Report type not specified']);
    exit;
}

$reportType = $_GET['type'];
$validTypes = ['daily', 'weekly', 'monthly'];

if (!in_array($reportType, $validTypes)) {
    echo json_encode(['error' => 'Invalid report type']);
    exit;
}

// Get current date
$currentDate = date('Y-m-d');

// Prepare date ranges based on report type
switch ($reportType) {
    case 'daily':
        $startDate = $currentDate;
        $endDate = $currentDate;
        $groupBy = "DATE_FORMAT(o.created_at, '%H:00')"; // Group by hour
        $dateFormat = "DATE_FORMAT(o.created_at, '%H:00')";
        break;
    case 'weekly':
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate = $currentDate;
        $groupBy = "DATE(o.created_at)";
        $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m-%d')";
        break;
    case 'monthly':
        $startDate = date('Y-m-01'); // First day of current month
        $endDate = date('Y-m-t'); // Last day of current month
        $groupBy = "DATE(o.created_at)";
        $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m-%d')";
        break;
}

// Query to get sales data
$query = "
    SELECT 
        $dateFormat AS date,
        COUNT(DISTINCT o.order_id) AS orders,
        SUM(oi.quantity) AS items_sold,
        SUM(oi.total) AS revenue
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    WHERE 
        o.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY 
        $groupBy
    ORDER BY 
        o.created_at ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

// Prepare data for response
$items = [];
$totalOrders = 0;
$totalItemsSold = 0;
$totalRevenue = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'date' => $row['date'],
            'orders' => (int)$row['orders'],
            'items_sold' => (int)$row['items_sold'],
            'revenue' => (float)$row['revenue']
        ];
        
        $totalOrders += (int)$row['orders'];
        $totalItemsSold += (int)$row['items_sold'];
        $totalRevenue += (float)$row['revenue'];
    }
}

// Prepare summary
$summary = [
    'total_orders' => $totalOrders,
    'total_items_sold' => $totalItemsSold,
    'total_revenue' => $totalRevenue
];

// Return response
echo json_encode([
    'items' => $items,
    'summary' => $summary
]);

$stmt->close();
$conn->close();
?>