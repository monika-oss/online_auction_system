<?php
session_start();
require 'db.php';
// At the top of your PHP scripts (process_bidding.php and place_bid.php)
date_default_timezone_set('Asia/Kolkata'); // Set your appropriate timezone

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: active_bids.php");
    exit();
}

$auction_id = intval($_GET['id']);

// Fetch auction details
$query = "SELECT a.*, u.username 
          FROM auctions a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Auction not found.");
}

$auction = $result->fetch_assoc();
// In place_bid.php after fetching auction details
if ($auction['status'] == 'active') {
    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $end_date = new DateTime($auction['end_date'], new DateTimeZone('Asia/Kolkata'));
    
    if ($now >= $end_date) {
        // Use transaction to prevent race conditions
        $conn->begin_transaction();
        try {
            // Lock auction row
            $stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $auction_id);
            $stmt->execute();
            $auction = $stmt->get_result()->fetch_assoc();

            if ($auction['status'] == 'active') {
                // Get highest bid
                $bid_query = "SELECT user_id, MAX(bid_amount) as max_bid FROM bids WHERE auction_id = ?";
                $bid_stmt = $conn->prepare($bid_query);
                $bid_stmt->bind_param("i", $auction_id);
                $bid_stmt->execute();
                $bid_result = $bid_stmt->get_result();
                
                if ($bid_result->num_rows > 0) {
                    $bid = $bid_result->fetch_assoc();
                    $update_query = "UPDATE auctions SET status='ended', winner_id=?, current_bid=? WHERE id=?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("idi", $bid['user_id'], $bid['max_bid'], $auction_id);
                } else {
                    $update_query = "UPDATE auctions SET status='ended' WHERE id=?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $auction_id);
                }
                $update_stmt->execute();
            }
            $conn->commit();
            
            // Redirect to winner.php after auction ends
            header("Location: winner.php?id=" . $auction_id);
            exit(); // Make sure to stop script execution here
            
        } catch (Exception $e) {
            $conn->rollback();
            die("Error ending auction: " . $e->getMessage());
        }
    }
}

// Handle bid submission if auction is still active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auction['status'] == 'active') {
    $bid_amount = floatval($_POST['bid_amount']);

    // Validate bid amount
    $min_bid = $auction['current_bid'] + $auction['bid_increment'];
    if ($bid_amount < $min_bid) {
        $error = "Your bid must be at least Rs. " . number_format($min_bid, 2);
    }
     // Get current time with timezone
     $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
     $end_date = new DateTime($auction['end_date'], new DateTimeZone('Asia/Kolkata'));
     
     // First check if auction has ended
     if ($now >= $end_date) {
         $error = "This auction has already ended.";
     } elseif ($bid_amount < $min_bid) {
         $error = "Your bid must be at least Rs. " . number_format($min_bid, 2);
     } else {
        // Update current bid in the database
        $update_query = "UPDATE auctions SET current_bid = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("di", $bid_amount, $auction_id);

        if ($update_stmt->execute()) {
            // Insert bid history
            $insert_query = "INSERT INTO bids (auction_id, user_id, bid_amount) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iid", $auction_id, $_SESSION['user_id'], $bid_amount);
            $insert_stmt->execute();

            $success = "Bid placed successfully!";
            // Refresh auction data
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $auction_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $auction = $result->fetch_assoc();
        } else {
            $error = "Failed to place bid. Please try again.";
        }
    }
}
if (isset($success)): ?>
    <p class="success"><?php echo $success; ?></p>
    <script>
        setTimeout(function() {
            window.location.reload();
        }, 2000); // Reload after 2 seconds
    </script>
<?php endif; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Bid - Online Auction</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .place-bid-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .auction-details {
            margin-bottom: 20px;
        }
        
        .auction-details img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .auction-title {
            font-size: 24px;
            margin: 10px 0;
        }
        
        .current-bid {
            font-size: 20px;
            color: #007bff;
            margin: 10px 0;
        }
        
        .bid-form {
            margin-top: 20px;
        }
        
        .bid-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .bid-form input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .bid-form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .bid-form button:hover {
            background-color: #0056b3;
        }
        
        .error {
            color: #e74c3c;
            margin: 10px 0;
        }
        
        .success {
            color: #2ecc71;
            margin: 10px 0;
        }
        .auction-ended {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #dc3545;
        }
        .payment-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
        }
        .payment-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <header style="background-color: blue;">
        <div class="logo">
            <img src="img/logo.jpeg" alt="Logo" style="width: 100px; height: 100px; border-radius:50%;">
        </div>
        <nav> 
            <ul>
            <li><a href="index.php">Home</a></li>
                <li><a href="about.html">AboutUs</a></li>
                <li><a href="auction_validate.html">Create Bid</a></li>
                <li><a href="membership.php">Membership</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="delivery_tracker.php">My order</a></li>
</ul>
        </nav>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['username'])): ?>
                <span style="color: white; margin-right: 10px;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <button onclick="window.location.href='logout.php'">Logout</button>
            <?php else: ?>
                <button onclick="window.location.href='login.php'">Login</button>
                <button onclick="window.location.href='signup.php'">SignUp</button>
            <?php endif; ?>
        </div>
    </header>
    <div class="place-bid-container">
        <h1>Place Bid</h1>
        
        <div class="auction-details">
            <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['item_name']); ?>">
            <h2 class="auction-title"><?php echo htmlspecialchars($auction['item_name']); ?></h2>
            <p><?php echo htmlspecialchars($auction['description']); ?></p>
            <p class="starting-bid">Starting Bid: Rs. <?php echo number_format($auction['starting_bid'], 2); ?></p>
            <p class="current-bid">Current Bid: Rs. <?php echo number_format($auction['current_bid'], 2); ?></p>
            <p>End Time: <?php 
            $display_date = new DateTime($auction['end_date'], new DateTimeZone('Asia/Kolkata'));
            echo $display_date->format('M j, Y g:i A'); ?></p>
            
        
        <div class="bid-form">
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <p class="success"><?php echo $success; ?></p>
                <script>
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                </script>
            <?php endif; ?>
            
            <?php if ($auction['status'] == 'active'): ?>
                <form method="POST">
                    <label for="bid_amount">Your Bid (Rs.):</label>
                    <input type="number" id="bid_amount" name="bid_amount" 
                           step="0.01" min="<?php echo $auction['current_bid'] + $auction['bid_increment']; ?>" required>
                    <button type="submit">Place Bid</button>
                </form>
            <?php else: ?>
                <button onclick="window.location.href='active_bids.php'">Back to Auctions</button>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Online Auction. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>

    