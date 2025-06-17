<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "auction_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
session_start();
require 'db.php';

// Validate session and parameters
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: active_bids.php");
    exit();
}

$auction_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch auction details and verify the current user is the winner
$query = "SELECT a.*, u.username AS seller_username, w.username AS winner_username
          FROM auctions a
          JOIN users u ON a.user_id = u.id
          LEFT JOIN users w ON a.winner_id = w.id
          WHERE a.id = ? AND a.winner_id = ? AND a.status = 'ended'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $auction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid auction or you are not the winner of this auction.");
}

$auction = $result->fetch_assoc();

// Generate a unique payment reference
$payment_ref = "AUCTION-" . $auction_id . "-" . time();

// Calculate total payment amount (starting_bid + current_bid)
$total_payment = $auction['starting_bid'] + $auction['current_bid'];

// Payment details (configure these according to your payment gateway)
$payment_details = [
    'amount' => $total_payment,
    'currency' => 'INR',
    'description' => "Payment for auction: " . $auction['item_name'],
    'reference' => $payment_ref,
    'callback_url' => 'https://yourauctionsite.com/payment_callback.php'
];

// Generate UPI payment URL (example using UPI scheme)
$upi_id = "yourauction@upi"; // Replace with your actual UPI ID
$payment_url = "upi://pay?pa=" . urlencode($upi_id) . 
              "&pn=" . urlencode("Online Auction") . 
              "&am=" . urlencode($payment_details['amount']) . 
              "&cu=" . urlencode($payment_details['currency']) . 
              "&tn=" . urlencode($payment_details['description']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify payment and update status
    $update_query = "UPDATE auctions SET payment_status = 'paid' WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $auction_id);
    
    if ($update_stmt->execute()) {
        $success = "Payment confirmed! Thank you for your purchase.";
        $auction['payment_status'] = 'paid';
    } else {
        $error = "Failed to update payment status. Please contact support.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .payment-container {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .price {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            border: 1px solid #eee;
            padding: 10px;
            background: white;
        }
        .payment-methods {
            margin: 20px 0;
        }
        .payment-btn {
            display: inline-block;
            background-color:rgb(40, 108, 167);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin: 10px 5px;
        }
        .success {
            color: #2ecc71;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Complete Your Payment</h1>
    
    <div class="payment-container">
        <h2>Item: <?php echo htmlspecialchars($auction['item_name']); ?></h2>
        <div class="price">Amount: Rs. <?php echo number_format($payment_details['amount'], 2); ?></div>
        <p>Payment Reference: <?php echo htmlspecialchars($payment_ref); ?></p>
        
            <div class="payment-methods">
                <h3>Choose Payment Method:</h3>

                <!-- UPI QR Code (using internal generation) -->
                <div style="text-align: center;">
                    <h4>Scan UPI QR Code:</h4>
                    <div class="qr-code">
                        <img src="img/payment_qr.jpeg" alt="UPI QR Code" style="max-width: 100%;">
                    </div>
                    <p>Scan this QR code with any UPI app</p>
                </div>
                
                <!-- Manual payment confirmation form -->
                <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
                    <h4>Already Paid?</h4>
                        <button onclick="window.location.href='delivery_form.php'" value='Yes' class="payment-btn">
                    <p>Yes</p>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php elseif (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
    </div>
    
    <footer>
        <p>Need help? Contact: udealauction4@gmail.com</p>
        <p>&copy; <?php echo date('Y'); ?> Online Auction. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>