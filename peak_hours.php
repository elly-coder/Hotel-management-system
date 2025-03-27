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

// Get date range parameters
$dateRange = isset($_GET['range']) ? $_GET['range'] : 'last7days';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Set date range based on selection
switch ($dateRange) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'last7days':
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate = date('Y-m-d');
        break;
    case 'last30days':
        $startDate = date('Y-m-d', strtotime('-29 days'));
        $endDate = date('Y-m-d');
        break;
    case 'custom':
        // Use the provided dates
        if (!$startDate || !$endDate) {
            echo json_encode(['error' => 'Custom date range requires start_date and end_date parameters']);
            exit;
        }
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate = date('Y-m-d');
}

// Query to get hourly order data
$query = "
    SELECT 
        HOUR(o.created_at) AS hour,
        COUNT(DISTINCT o.order_id) AS orders,
        SUM(oi.quantity) AS items_sold,
        SUM(oi.total) AS revenue
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    WHERE 
        DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY 
        HOUR(o.created_at)
    ORDER BY 
        hour ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

// Prepare data arrays for all hours (6am to 10pm)
$hours = [];
$ordersData = [];
$itemsSoldData = [];
$revenueData = [];

// Initialize arrays with zeros for all hours
for ($i = 6; $i <= 22; $i++) {
    $hours[] = sprintf("%02d:00", $i);
    $ordersData[$i] = 0;
    $itemsSoldData[$i] = 0;
    $revenueData[$i] = 0;
}

// Fill in actual data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hour = (int)$row['hour'];
        if ($hour >= 6 && $hour <= 22) { // Only include business hours
            $ordersData[$hour] = (int)$row['orders'];
            $itemsSoldData[$hour] = (int)$row['items_sold'];
            $revenueData[$hour] = (float)$row['revenue'];
        }
    }
}

// Convert associative arrays to indexed arrays for the response
$orders = array_values($ordersData);
$itemsSold = array_values($itemsSoldData);
$revenue = array_values($revenueData);

// Calculate totals
$totalOrders = array_sum($ordersData);
$totalItemsSold = array_sum($itemsSoldData);
$totalRevenue = array_sum($revenueData);

// Find peak hours (top 3)
arsort($ordersData); // Sort by orders, highest first
$peakHoursByOrders = array_slice(array_keys($ordersData), 0, 3, true);

arsort($revenueData); // Sort by revenue, highest first
$peakHoursByRevenue = array_slice(array_keys($revenueData), 0, 3, true);

// Format peak hours
$formattedPeakHoursByOrders = [];
foreach ($peakHoursByOrders as $hour) {
    $formattedPeakHoursByOrders[] = sprintf("%02d:00", $hour);
}

$formattedPeakHoursByRevenue = [];
foreach ($peakHoursByRevenue as $hour) {
    $formattedPeakHoursByRevenue[] = sprintf("%02d:00", $hour);
}

// Calculate average order values
$avgOrderValues = [];
foreach ($hours as $index => $hour) {
    $avgOrderValues[$index] = $orders[$index] > 0 ? $revenue[$index] / $orders[$index] : 0;
}

// Prepare response
$response = [
    'hours' => $hours,
    'orders' => $orders,
    'itemsSold' => $itemsSold,
    'revenue' => $revenue,
    'avgOrderValues' => $avgOrderValues,
    'summary' => [
        'total_orders' => $totalOrders,
        'total_items_sold' => $totalItemsSold,
        'total_revenue' => $totalRevenue,
        'peak_hours_by_orders' => $formattedPeakHoursByOrders,
        'peak_hours_by_revenue' => $formattedPeakHoursByRevenue,
        'date_range' => [
            'start' => $startDate,
            'end' => $endDate
        ]
    ]
];

// Return response
echo json_encode($response);

$stmt->close();
$conn->close();
?>

