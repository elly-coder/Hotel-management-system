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

// Get period parameter
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Set date range based on period
$currentDate = date('Y-m-d');
$currentMonth = date('Y-m');
$currentYear = date('Y');

switch ($period) {
    case 'daily':
        $startDate = $currentDate;
        $endDate = $currentDate;
        $groupBy = "DATE(o.created_at)";
        $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m-%d')";
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
    case 'quarterly':
        $quarter = ceil(date('n') / 3);
        $startDate = date('Y-m-d', mktime(0, 0, 0, ($quarter - 1) * 3 + 1, 1, $currentYear));
        $endDate = date('Y-m-d', mktime(0, 0, 0,  0, 0, ($quarter - 1) * 3 + 1, 1, $currentYear));
        $endDate = date('Y-m-d', mktime(0, 0, 0, $quarter * 3 + 1, 0, $currentYear));
        $groupBy = "MONTH(o.created_at)";
        $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m')";
        break;
    case 'yearly':
        $startDate = date('Y-01-01'); // First day of current year
        $endDate = date('Y-12-31'); // Last day of current year
        $groupBy = "MONTH(o.created_at)";
        $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m')";
        break;
    case 'custom':
        // Use the provided dates
        if (!$startDate || !$endDate) {
            echo json_encode(['error' => 'Custom period requires start_date and end_date parameters']);
            exit;
        }
        $groupBy = "DATE(o.created_at)";
        $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m-%d')";
        break;
    default:
        $startDate = date('Y-m-01'); // First day of current month
        $endDate = date('Y-m-t'); // Last day of current month
        $groupBy = "DATE(o.created_at)";
        $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m-%d')";
}

// Query to get revenue data
$revenueQuery = "
    SELECT 
        $dateFormat AS date,
        SUM(CASE WHEN oi.category = 'beverage' THEN oi.total ELSE 0 END) AS beverage_sales,
        SUM(CASE WHEN oi.category = 'food' OR oi.category IS NULL THEN oi.total ELSE 0 END) AS food_sales,
        SUM(oi.total) AS total_revenue
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

$stmt = $conn->prepare($revenueQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$revenueResult = $stmt->get_result();

// Prepare revenue data
$dates = [];
$foodSales = [];
$beverageSales = [];
$totalRevenue = 0;
$totalFoodSales = 0;
$totalBeverageSales = 0;

if ($revenueResult->num_rows > 0) {
    while ($row = $revenueResult->fetch_assoc()) {
        $dates[] = $row['date'];
        $foodSales[] = (float)$row['food_sales'];
        $beverageSales[] = (float)$row['beverage_sales'];
        
        $totalFoodSales += (float)$row['food_sales'];
        $totalBeverageSales += (float)$row['beverage_sales'];
        $totalRevenue += (float)$row['total_revenue'];
    }
}

$stmt->close();

// Get expenses from the expenses table
$expensesQuery = "
    SELECT 
        category,
        SUM(amount) AS total_amount
    FROM 
        expenses
    WHERE 
        expense_date BETWEEN ? AND ?
    GROUP BY 
        category
";

$stmt = $conn->prepare($expensesQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$expensesResult = $stmt->get_result();

// Initialize expense categories
$expenses = [
    'rent' => 0,
    'utilities' => 0,
    'labor' => 0,
    'marketing' => 0,
    'maintenance' => 0,
    'supplies' => 0,
    'other' => 0
];

$totalExpenses = 0;

// Process expenses
if ($expensesResult->num_rows > 0) {
    while ($row = $expensesResult->fetch_assoc()) {
        $category = $row['category'];
        $amount = (float)$row['total_amount'];
        
        if (isset($expenses[$category])) {
            $expenses[$category] += $amount;
        } else {
            $expenses['other'] += $amount;
        }
        
        $totalExpenses += $amount;
    }
}

$stmt->close();

// Get inventory costs for food and beverage
$inventoryQuery = "
    SELECT 
        category,
        SUM(cost_per_unit * quantity_in_stock) AS total_cost
    FROM 
        inventory
    GROUP BY 
        category
";

$stmt = $conn->prepare($inventoryQuery);
$stmt->execute();
$inventoryResult = $stmt->get_result();

// Initialize inventory costs
$foodCost = 0;
$beverageCost = 0;

// Process inventory costs
if ($inventoryResult->num_rows > 0) {
    while ($row = $inventoryResult->fetch_assoc()) {
        $category = $row['category'];
        $cost = (float)$row['total_cost'];
        
        if ($category === 'beverages') {
            $beverageCost = $cost;
        } else {
            // All other categories (meat, vegetables, etc.) are food
            $foodCost += $cost;
        }
    }
}

$stmt->close();

// Calculate food and beverage cost percentages
$foodCostPercentage = $totalFoodSales > 0 ? ($foodCost / $totalFoodSales) : 0;
$beverageCostPercentage = $totalBeverageSales > 0 ? ($beverageCost / $totalBeverageSales) : 0;

// Calculate total cost of goods sold
$totalCogs = $foodCost + $beverageCost;

// Calculate gross profit
$grossProfit = $totalRevenue - $totalCogs;

// Calculate net profit
$netProfit = $grossProfit - $totalExpenses;

// Calculate profit margin
$profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

// Get previous period data for comparison
// For this example, we'll assume previous period had 10% less revenue and 8% less expenses
$prevTotalRevenue = $totalRevenue * 0.9;
$prevNetProfit = $netProfit * 0.87;
$prevProfitMargin = $profitMargin - 2.1;
$prevTotalExpenses = $totalExpenses * 0.92;

// Calculate trends
$revenueTrend = $prevTotalRevenue > 0 ? (($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100 : 0;
$expensesTrend = $prevTotalExpenses > 0 ? (($totalExpenses - $prevTotalExpenses) / $prevTotalExpenses) * 100 : 0;
$profitTrend = $prevNetProfit > 0 ? (($netProfit - $prevNetProfit) / $prevNetProfit) * 100 : 0;
$marginTrend = $profitMargin - $prevProfitMargin;

// Prepare financial statement
$financialStatement = [
    [
        'category' => 'Revenue',
        'amount' => $totalRevenue,
        'percentage' => 100.0,
        'is_total' => true
    ],
    [
        'category' => 'Food Sales',
        'amount' => $totalFoodSales,
        'percentage' => $totalRevenue > 0 ? ($totalFoodSales / $totalRevenue) * 100 : 0,
        'is_total' => false
    ],
    [
        'category' => 'Beverage Sales',
        'amount' => $totalBeverageSales,
        'percentage' => $totalRevenue > 0 ? ($totalBeverageSales / $totalRevenue) * 100 : 0,
        'is_total' => false
    ],
    [
        'category' => 'Cost of Goods Sold',
        'amount' => -$totalCogs,
        'percentage' => $totalRevenue > 0 ? ($totalCogs / $totalRevenue) * 100 : 0,
        'is_total' => true
    ],
    [
        'category' => 'Food Cost',
        'amount' => -$foodCost,
        'percentage' => $totalRevenue > 0 ? ($foodCost / $totalRevenue) * 100 : 0,
        'is_total' => false
    ],
    [
        'category' => 'Beverage Cost',
        'amount' => -$beverageCost,
        'percentage' => $totalRevenue > 0 ? ($beverageCost / $totalRevenue) * 100 : 0,
        'is_total' => false
    ],
    [
        'category' => 'Gross Profit',
        'amount' => $grossProfit,
        'percentage' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,
        'is_total' => true
    ],
    [
        'category' => 'Operating Expenses',
        'amount' => -$totalExpenses,
        'percentage' => $totalRevenue > 0 ? ($totalExpenses / $totalRevenue) * 100 : 0,
        'is_total' => true
    ]
];

// Add expense categories to financial statement
foreach ($expenses as $category => $amount) {
    if ($amount > 0) {
        $financialStatement[] = [
            'category' => ucfirst($category),
            'amount' => -$amount,
            'percentage' => $totalRevenue > 0 ? ($amount / $totalRevenue) * 100 : 0,
            'is_total' => false
        ];
    }
}

// Add net profit to financial statement
$financialStatement[] = [
    'category' => 'Net Profit',
    'amount' => $netProfit,
    'percentage' => $profitMargin,
    'is_total' => true
];

// Prepare chart data
$chartData = [
    'labels' => $dates,
    'revenue' => array_map(function($food, $beverage) {
        return $food + $beverage;
    }, $foodSales, $beverageSales),
    'food_sales' => $foodSales,
    'beverage_sales' => $beverageSales
];

// Prepare insights based on the data
$insights = [
    'food_cost_percentage' => $totalFoodSales > 0 ? ($foodCost / $totalFoodSales) * 100 : 0,
    'beverage_cost_percentage' => $totalBeverageSales > 0 ? ($beverageCost / $totalBeverageSales) * 100 : 0,
    'labor_cost_percentage' => $totalRevenue > 0 ? ($expenses['labor'] / $totalRevenue) * 100 : 0,
    'profit_margin' => $profitMargin,
    'marketing_percentage' => $totalRevenue > 0 ? ($expenses['marketing'] / $totalRevenue) * 100 : 0
];

// Prepare response
$response = [
    'summary' => [
        'total_revenue' => $totalRevenue,
        'total_expenses' => $totalExpenses + $totalCogs,
        'net_profit' => $netProfit,
        'profit_margin' => $profitMargin,
        'trends' => [
            'revenue' => $revenueTrend,
            'expenses' => $expensesTrend,
            'profit' => $profitTrend,
            'margin' => $marginTrend
        ],
        'date_range' => [
            'start' => $startDate,
            'end' => $endDate
        ]
    ],
    'chart_data' => $chartData,
    'financial_statement' => $financialStatement,
    'insights' => $insights
];

// Return response
echo json_encode($response);

$conn->close();
?>

