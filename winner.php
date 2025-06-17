<?php
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

// 1. First fetch auction basic info
$auction_query = "SELECT a.*, u.username as seller_username 
                 FROM auctions a
                 JOIN users u ON a.user_id = u.id
                 WHERE a.id = ?";
$auction_stmt = $conn->prepare($auction_query);
$auction_stmt->bind_param("i", $auction_id);
$auction_stmt->execute();
$auction_result = $auction_stmt->get_result();

if ($auction_result->num_rows === 0) {
    die("Auction not found or you don't have permission to view it.");
}

$auction = $auction_result->fetch_assoc();

// Verify auction has ended
if ($auction['status'] != 'ended') {
    die("This auction is still active.");
}

// 2. Find the ACTUAL winning bid (most important query)
$winner_query = "SELECT b.user_id, u.username, b.bid_amount
                FROM bids b
                JOIN users u ON b.user_id = u.id
                WHERE b.auction_id = ?
                ORDER BY b.bid_amount DESC
                LIMIT 1";
$winner_stmt = $conn->prepare($winner_query);
$winner_stmt->bind_param("i", $auction_id);
$winner_stmt->execute();
$winner_result = $winner_stmt->get_result();

$winning_bid = null;
if ($winner_result->num_rows > 0) {
    $winning_bid = $winner_result->fetch_assoc();
    
    // 3. Verify database consistency 
    if (!$auction['winner_id'] || $auction['winner_id'] != $winning_bid['user_id'] || $auction['current_bid'] != $winning_bid['bid_amount']) {
        // Update auction table with correct winner info
        $update_query = "UPDATE auctions SET 
                        winner_id = ?,
                        current_bid = ?
                        WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("idi", 
            $winning_bid['user_id'],
            $winning_bid['bid_amount'],
            $auction_id);
        $update_stmt->execute();
    }
}

// 4. Compare with current user
$is_winner = ($winning_bid && $winning_bid['user_id'] == $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Results</title>
    <style>
    :root {
        --primary-color: #4361ee;
        --success-color: #38b000;
        --danger-color: #ef233c;
        --light-gray: #f8f9fa;
        --dark-gray: #333;
        --white: #fff;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--light-gray);
        color: var(--dark-gray);
        line-height: 1.6;
        padding: 2rem;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
    }

    h1 {
        color: var(--primary-color);
        margin-bottom: 1.5rem;
        font-size: 2.2rem;
        text-align: center;
        position: relative;
        padding-bottom: 0.5rem;
    }

    h1::after {
        content: '';
        display: block;
        width: 80px;
        height: 4px;
        background: var(--primary-color);
        margin: 0.5rem auto 0;
        border-radius: 2px;
    }

    .price {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--success-color);
        text-align: center;
        margin-bottom: 1.5rem;
        padding: 0.5rem;
    }

    .winner-box, .info-box {
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        text-align: center;
    }

    .winner-box:hover, .info-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .winner-box {
        background: linear-gradient(135deg, #d4edda, #c8f7d4);
        border-left: 5px solid var(--success-color);
    }

    .info-box {
        background: linear-gradient(135deg, #e2e3e5, #f0f1f2);
        border-left: 5px solid #adb5bd;
    }

    .winner-box h2 {
        color: var(--success-color);
        font-size: 1.8rem;
        margin-bottom: 1rem;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .winner-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: var(--success-color);
        color: var(--white);
        border-radius: 20px;
        margin-bottom: 1rem;
        animation: glow 1.5s infinite alternate;
    }

    @keyframes glow {
        from { box-shadow: 0 0 5px rgba(56, 176, 0, 0.5); }
        to { box-shadow: 0 0 15px rgba(56, 176, 0, 0.8); }
    }

    .payment-button {
        display: inline-block;
        padding: 0.8rem 2rem;
        background: var(--success-color);
        color: var(--white);
        border: none;
        border-radius: 50px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 1rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .payment-button:hover {
        background: #2e8b57;
        transform: translateY(-2px);
    }

    .flex-center {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
    }

    a.back-link {
        display: inline-block;
        padding: 0.6rem 1.5rem;
        background: var(--primary-color);
        color: var(--white);
        border-radius: 50px;
        transition: var(--transition);
        text-decoration: none;
        margin-top: 1rem;
    }

    a.back-link:hover {
        background: #354fbe;
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        h1 {
            font-size: 1.8rem;
        }

        .price {
            font-size: 1.5rem;
        }

        .winner-box, .info-box {
            padding: 1.5rem;
        }

        .payment-button, a.back-link {
            padding: 0.6rem 1.2rem;
        }
    }
</style>
</head>
<body>
    <h1>Auction Results: <?php echo htmlspecialchars($auction['item_name']); ?></h1>
    
    <div class="price">
        Final Price: Rs. <?php echo number_format($winning_bid['bid_amount'] ?? $auction['starting_bid'], 2); ?>
    </div>

    <?php if ($is_winner): ?>
        <div class="winner-box">
            <h2>🎉 You Won! 🎉</h2>
            <p>Your bid of Rs. <?php echo number_format($winning_bid['bid_amount'], 2); ?> was the highest!</p>
            <a href="payment.php?id=<?= $auction_id ?>" class="payment-button">Proceed to Payment</a>
        </div>
    <?php else: ?>
        <div class="info-box">
            <?php if ($winning_bid): ?>
                <p>Winner: <strong><?php echo htmlspecialchars($winning_bid['username']); ?></strong></p>
                <p>Winning Bid: Rs. <?php echo number_format($winning_bid['bid_amount'], 2); ?></p>
            <?php else: ?>
                <p>This auction received no bids.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p><a href="active_bids.php">&larr; Back to auctions</a></p>
</body>
</html>
<?php $conn->close(); ?>