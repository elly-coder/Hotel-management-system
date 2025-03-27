<?php 
// Debug information 
error_log("Payment.php accessed with parameters: " . json_encode($_GET)); 
error_log("Server path: " . $_SERVER['SCRIPT_FILENAME']); 
 
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
$table_number = isset($_GET['table']) ? $_GET['table'] : null; 
 
// Initialize variables 
$order_items = []; 
$total_amount = 0; 
$customer_name = ""; 
$customer_phone = ""; 
 
// If order ID is provided, fetch order details 
if ($order_id) { 
    // Fetch order details 
    $order_query = "SELECT o.*  
                FROM orders o  
                WHERE o.order_id = ?"; 
    $stmt = $conn->prepare($order_query); 
    $stmt->bind_param("i", $order_id); 
    $stmt->execute(); 
    $order_result = $stmt->get_result(); 
 
    if ($order_result->num_rows > 0) { 
        $order_data = $order_result->fetch_assoc(); 
        $table_number = $order_data['table_number']; 
        $total_amount = $order_data['total_amount']; 
        // No customer data available 
        $customer_name = "Guest"; 
        $customer_phone = ""; 
         
        // Fetch order items 
        $items_query = "SELECT * FROM order_items WHERE order_id = ?"; 
        $stmt = $conn->prepare($items_query); 
        $stmt->bind_param("i", $order_id); 
        $stmt->execute(); 
        $items_result = $stmt->get_result(); 
         
        while ($item = $items_result->fetch_assoc()) { 
            $order_items[] = $item; 
        } 
    } else { 
        // Order not found, redirect to error page 
        header("Location: ../error.php?message=Order not found"); 
        exit; 
    } 
} else if ($table_number) { 
    // If only table number is provided, fetch the latest order for this table
    $order_query = "SELECT o.*  
                FROM orders o  
                WHERE o.table_number = ?  
                ORDER BY o.created_at DESC LIMIT 1"; 
    $stmt = $conn->prepare($order_query); 
    $stmt->bind_param("i", $table_number); 
    $stmt->execute(); 
    $order_result = $stmt->get_result(); 
 
    if ($order_result->num_rows > 0) { 
        $order_data = $order_result->fetch_assoc(); 
        $order_id = $order_data['order_id']; 
        $total_amount = $order_data['total_amount']; 
        // No customer data available 
        $customer_name = "Guest"; 
        $customer_phone = ""; 
         
        // Fetch order items 
        $items_query = "SELECT * FROM order_items WHERE order_id = ?"; 
        $stmt = $conn->prepare($items_query); 
        $stmt->bind_param("i", $order_id); 
        $stmt->execute(); 
        $items_result = $stmt->get_result(); 
         
        while ($item = $items_result->fetch_assoc()) { 
            $order_items[] = $item; 
        } 
    } else { 
        // No pending order found for this table 
        $no_order = true; 
    } 
} else { 
    // Neither order ID nor table number provided 
    header("Location: ../error.php?message=No order or table specified"); 
    exit; 
} 
 
// Process payment form submission 
if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $payment_method = $_POST['payment_method']; 
    $amount_paid = $_POST['amount']; 
    $transaction_id = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : ''; 
     
    // Validate payment amount 
    if ($amount_paid < $total_amount) { 
        $payment_error = "Payment amount must be at least KSh " . number_format($total_amount, 2); 
    } else { 
        // Insert payment record 
        $payment_query = "INSERT INTO payments (order_id, payment_method, amount, 
transaction_id, payment_date)  
                          VALUES (?, ?, ?, ?, NOW())"; 
        $stmt = $conn->prepare($payment_query); 
        $stmt->bind_param("isds", $order_id, $payment_method, $amount_paid, $transaction_id); 
         
        if ($stmt->execute()) { 
            // Check if payment_status column exists in orders table
            $check_column = "SHOW COLUMNS FROM orders LIKE 'payment_status'";
            $column_result = $conn->query($check_column);
            
            if ($column_result->num_rows > 0) {
                // Column exists, update it with 'paid' value
                $update_query = "UPDATE orders SET payment_status = 'paid' WHERE order_id = ?"; 
                $stmt = $conn->prepare($update_query); 
                $stmt->bind_param("i", $order_id); 
                $stmt->execute(); 
            }
            
            // Redirect to print_receipt.php with the order ID
            // Use "../" to go up one directory level from /login/menu/ to /login/
            header("Location: ../print_receipt.php?order_id=" . $order_id); 
            exit; 
        } else { 
            $payment_error = "Payment processing failed. Please try again."; 
        } 
    } 
} 
 
$conn->close(); 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>NEGAD GRILL - Payment</title> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-
awesome/6.4.0/css/all.min.css"> 
    <style> 
        /* Basic Reset */ 
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        } 
         
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f5f5f5; 
            color: #333; 
            line-height: 1.6; 
        } 
         
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
        } 
         
        header { 
            background-color: lightcoral; 
            color: white; 
            text-align: center; 
            padding: 15px; 
            margin-bottom: 20px; 
        } 
         
        header h1 { 
            margin: 0; 
            font-size: 2rem; 
        } 
         
        .payment-container { 
            background-color: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); 
            padding: 20px; 
            margin-bottom: 20px; 
        } 
         
        .payment-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-bottom: 15px; 
            border-bottom: 1px solid #e0e0e0; 
            margin-bottom: 20px; 
        } 
         
        .payment-header h2 { 
            font-size: 1.5rem; 
            color: #333; 
            margin: 0; 
        } 
         
        .order-summary { 
            margin-bottom: 20px; 
        } 
         
        .order-summary h3 { 
            margin-bottom: 10px; 
            font-size: 1.2rem; 
        } 
         
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        } 
         
        th, td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        } 
         
        th { 
            background-color: #f5f5f5; 
            font-weight: bold; 
        } 
         
        .total-row { 
            font-weight: bold; 
            background-color: #f9f9f9; 
        } 
         
        .payment-methods { 
            margin-bottom: 20px; 
        } 
         
        .payment-methods h3 { 
            margin-bottom: 10px; 
            font-size: 1.2rem; 
        } 
         
        .payment-options { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 20px; 
        } 
         
        .payment-option { 
            flex: 1; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            padding: 15px; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.3s; 
        } 
         
        .payment-option:hover { 
            border-color: #00BCD4; 
        } 
         
        .payment-option.selected { 
            border-color: #00BCD4; 
            background-color: rgba(0, 188, 212, 0.1); 
        } 
         
        .payment-option i { 
            font-size: 2rem; 
            margin-bottom: 10px; 
            color: #00BCD4; 
        } 
         
        .payment-form { 
            margin-top: 20px; 
        } 
         
        .form-group { 
            margin-bottom: 15px; 
        } 
         
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        } 
         
        .form-group input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            font-size: 1rem; 
        } 
         
        .btn { 
            display: inline-block; 
            background-color: #00BCD4; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 1rem; 
            font-weight: bold; 
            transition: background-color 0.3s; 
            text-decoration: none; 
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
         
        .text-center { 
            text-align: center; 
        } 
         
        .error-message { 
            color: #e74c3c; 
            background-color: #fde2e2; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px; 
        } 
         
        .no-order { 
            text-align: center; 
            padding: 40px 20px; 
        } 
         
        .no-order i { 
            font-size: 3rem; 
            color: #757575; 
            margin-bottom: 20px; 
        } 
         
        .no-order h3 { 
            margin-bottom: 15px; 
            font-size: 1.5rem; 
        } 
         
        @media (max-width: 768px) { 
            .payment-options { 
                flex-direction: column; 
            } 
        } 
    </style> 
</head> 
<body> 
    <header> 
        <h1>NEGAD GRILL</h1> 
    </header> 
     
    <div class="container"> 
        <?php if (isset($no_order) && $no_order): ?> 
            <div class="payment-container"> 
                <div class="no-order"> 
                    <i class="fas fa-exclamation-circle"></i> 
                    <h3>No Pending Order Found</h3> 
                    <p>There are no pending orders for Table <?php echo $table_number; ?>.</p> 
                    <div style="margin-top: 20px;"> 
                        <a href="../kitchen_dashboard.html" class="btn">Back to Kitchen</a> 
                    </div> 
                </div> 
            </div> 
        <?php else: ?> 
            <div class="payment-container"> 
                <div class="payment-header"> 
                    <h2>Payment</h2> 
                    <div> 
                        <span>Table <?php echo $table_number; ?></span> 
                        <?php if ($order_id): ?> 
                            <span> | Order #<?php echo $order_id; ?></span> 
                        <?php endif; ?> 
                    </div> 
                </div> 
                 
                <?php if (isset($payment_error)): ?> 
                    <div class="error-message"> 
                        <?php echo $payment_error; ?> 
                    </div> 
                <?php endif; ?> 
                 
                <div class="order-summary"> 
                    <h3>Order Summary</h3> 
                    <table> 
                        <thead> 
                            <tr> 
                                <th>Item</th> 
                                <th>Quantity</th> 
                                <th>Price</th> 
                                <th>Total</th> 
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
                                        <td><?php echo isset($item['item_name']) ? $item['item_name'] : 'Unknown 
Item'; ?></td> 
                                        <td><?php echo isset($item['quantity']) ? $item['quantity'] : 1; ?></td> 
                                        <td>KSh <?php echo isset($item['price']) ? number_format($item['price'], 2) : 
'0.00'; ?></td> 
                                        <td>KSh <?php echo isset($item['total']) ? number_format($item['total'], 2) : 
'0.00'; ?></td> 
                                    </tr> 
                                <?php endforeach; ?> 
                            <?php endif; ?> 
                            <tr class="total-row"> 
                                <td colspan="3">Total Amount</td> 
                                <td>KSh <?php echo number_format($total_amount, 2); ?></td> 
                            </tr> 
                        </tbody> 
                    </table> 
                </div> 
                 
                <div class="payment-methods"> 
                    <h3>Select Payment Method</h3> 
                    <div class="payment-options"> 
                        <div class="payment-option" id="card-option" onclick="selectPaymentMethod('card')"> 
                            <i class="fas fa-credit-card"></i> 
                            <h4>Card Payment</h4> 
                        </div> 
                        <div class="payment-option" id="mpesa-option" 
onclick="selectPaymentMethod('mpesa')"> 
                            <i class="fas fa-mobile-alt"></i> 
                            <h4>M-Pesa</h4> 
                        </div> 
                    </div> 
                     
                    <form id="payment-form" method="POST" action=""> 
                        <input type="hidden" name="payment_method" id="payment_method" value="card"> 
                         
                        <div id="card-fields" class="payment-form"> 
                            <div class="form-group"> 
                                <label for="card-number">Card Number</label> 
                                <input type="text" id="card-number" placeholder="Enter card number" 
maxlength="16"> 
                            </div> 
                            <div class="form-group"> 
                                <label for="card-name">Cardholder Name</label> 
                                <input type="text" id="card-name" placeholder="Enter cardholder name"> 
                            </div> 
                            <div style="display: flex; gap: 15px;"> 
                                <div class="form-group" style="flex: 1;"> 
                                    <label for="expiry-date">Expiry Date</label> 
                                    <input type="text" id="expiry-date" placeholder="MM/YY" maxlength="5"> 
                                </div> 
                                <div class="form-group" style="flex: 1;"> 
                                    <label for="cvv">CVV</label> 
                                    <input type="text" id="cvv" placeholder="CVV" maxlength="3"> 
                                </div> 
                            </div> 
                        </div> 
                         
                        <div id="mpesa-fields" class="payment-form" style="display: none;"> 
                            <div class="form-group"> 
                                <label for="phone-number">M-Pesa Phone Number</label> 
                                <input type="text" id="phone-number" placeholder="Enter phone number" 
maxlength="12"> 
                            </div> 
                            <div class="form-group"> 
                                <label for="transaction-id">Transaction ID (Optional)</label> 
                                <input type="text" id="transaction-id" name="transaction_id" placeholder="Enter 
M-Pesa transaction ID"> 
                            </div> 
                        </div> 
                         
                        <div class="form-group"> 
                            <label for="amount">Amount to Pay</label> 
                            <input type="number" id="amount" name="amount" value="<?php echo 
$total_amount; ?>" min="<?php echo $total_amount; ?>" step="0.01" required> 
                        </div> 
                         
                        <div class="text-center" style="margin-top: 20px;"> 
                            <button type="button" class="btn btn-secondary" 
onclick="window.history.back()">Back</button> 
                            <button type="submit" class="btn">Complete Payment</button> 
                        </div> 
                    </form> 
                </div> 
            </div> 
        <?php endif; ?> 
    </div> 
     
    <script> 
        function selectPaymentMethod(method) { 
            // Update hidden input 
            document.getElementById('payment_method').value = method; 
             
            // Update UI 
            if (method === 'card') { 
                document.getElementById('card-option').classList.add('selected'); 
                document.getElementById('mpesa-option').classList.remove('selected'); 
                document.getElementById('card-fields').style.display = 'block'; 
                document.getElementById('mpesa-fields').style.display = 'none'; 
            } else { 
                document.getElementById('mpesa-option').classList.add('selected'); 
                document.getElementById('card-option').classList.remove('selected'); 
                document.getElementById('mpesa-fields').style.display = 'block'; 
                document.getElementById('card-fields').style.display = 'none'; 
            } 
        } 
         
        // Initialize with card payment selected 
        window.onload = function() { 
            selectPaymentMethod('card'); 
        }; 
    </script> 
</body> 
</html>