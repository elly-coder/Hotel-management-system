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
    die("Connection failed: " . $conn->connect_error);
}

// Get order ID from URL parameter
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if (!$order_id) {
    header("Location: error.php?message=No order specified");
    exit;
}

// Initialize variables
$order_items = [];
$order_data = null;
$payment_data = null;

// Fetch order details
$order_query = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows > 0) {
    $order_data = $order_result->fetch_assoc();
    
    // Fetch order items
    $items_query = "SELECT * FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
    
    // Fetch payment details
    $payment_query = "SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC LIMIT 1";
    $stmt = $conn->prepare($payment_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $payment_result = $stmt->get_result();
    
    if ($payment_result->num_rows > 0) {
        $payment_data = $payment_result->fetch_assoc();
    }
} else {
    header("Location: error.php?message=Order not found");
    exit;
}

$conn->close();

// Calculate change if applicable
$change = 0;
if ($payment_data && $payment_data['amount'] > $order_data['total_amount']) {
    $change = $payment_data['amount'] - $order_data['total_amount'];
}

// Format date
$order_date = new DateTime($order_data['created_at']);
$formatted_date = $order_date->format('M d, Y h:i A');

// Format payment date if available
$payment_date_formatted = '';
if ($payment_data && isset($payment_data['payment_date'])) {
    $payment_date = new DateTime($payment_data['payment_date']);
    $payment_date_formatted = $payment_date->format('M d, Y h:i A');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEGAD GRILL - Receipt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Basic Reset */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .receipt {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 20px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ddd;
        }
        
        .receipt-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .receipt-header p {
            font-size: 14px;
            color: #666;
        }
        
        .receipt-info {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .receipt-info div {
            flex: 1;
        }
        
        .receipt-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .receipt-info p {
            font-size: 14px;
            color: #666;
        }
        
        .receipt-items {
            margin-bottom: 20px;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        
        th {
            border-bottom: 1px solid #ddd;
        }
        
        .amount {
            text-align: right;
        }
        
        .receipt-totals {
            margin-bottom: 20px;
        }
        
        .receipt-totals table {
            width: 100%;
            max-width: 300px;
            margin-left: auto;
        }
        
        .receipt-payment {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ddd;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
        }
        
        .receipt-footer p {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            background-color: #00BCD4;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .btn:hover {
            background-color: #0097A7;
        }
        
        .btn-secondary {
            background-color: #757575;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        @media print {
            .actions {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .container {
                padding: 0;
            }
            
            .receipt {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt" id="receipt">
            <div class="receipt-header">
                <h1>NEGAD GRILL</h1>
                <p>123 Main Street, Nairobi, Kenya</p>
                <p>Tel: +254 123 456 789</p>
                <p>Email: info@negadgrill.com</p>
                <h2>RECEIPT</h2>
            </div>
            
            <div class="receipt-info">
                <div>
                    <h3>Order Information</h3>
                    <p>Order #: <?php echo $order_id; ?></p>
                    <p>Date: <?php echo $formatted_date; ?></p>
                    <p>Table #: <?php echo $order_data['table_number']; ?></p>
                </div>
                
                <div>
                    <h3>Payment Information</h3>
                    <p>Payment Method: <?php echo isset($payment_data['payment_method']) ? ucfirst($payment_data['payment_method']) : 'N/A'; ?></p>
                    <p>Payment Date: <?php echo $payment_date_formatted; ?></p>
                    <?php if (isset($payment_data['transaction_id']) && !empty($payment_data['transaction_id'])): ?>
                        <p>Transaction ID: <?php echo $payment_data['transaction_id']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="receipt-items">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th class="amount">Price</th>
                            <th class="amount">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($order_items)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No items found for this order</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo isset($item['item_name']) ? $item['item_name'] : 'Unknown Item'; ?></td>
                                    <td><?php echo isset($item['quantity']) ? $item['quantity'] : 1; ?></td>
                                    <td class="amount">KSh <?php echo isset($item['price']) ? number_format($item['price'], 2) : '0.00'; ?></td>
                                    <td class="amount">KSh <?php echo isset($item['total']) ? number_format($item['total'], 2) : '0.00'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="receipt-totals">
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td class="amount">KSh <?php echo number_format($order_data['total_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Tax (0%):</td>
                        <td class="amount">KSh 0.00</td>
                    </tr>
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td class="amount"><strong>KSh <?php echo number_format($order_data['total_amount'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="receipt-payment">
                <table>
                    <tr>
                        <td>Amount Paid:</td>
                        <td class="amount">KSh <?php echo isset($payment_data['amount']) ? number_format($payment_data['amount'], 2) : '0.00'; ?></td>
                    </tr>
                    <?php if ($change > 0): ?>
                    <tr>
                        <td>Change:</td>
                        <td class="amount">KSh <?php echo number_format($change, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="receipt-footer">
                <p>Thank you for dining with us!</p>
                <p>We appreciate your business and hope to see you again soon.</p>
                <p>Receipt #: <?php echo sprintf('%06d', $order_id); ?></p>
                <p><?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
        
        <div class="actions">
            <button class="btn" onclick="window.print()">Print Receipt</button>
            <a href="NEGADMENU.html" class="btn btn-secondary">Back to Menu</a>
        </div>
    </div>
<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script></body>
</html>