<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle delivery form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_address'])) {
    $auction_id = intval($_POST['auction_id']);
    $delivery_address = $_POST['delivery_address'];
    $delivery_phone = $_POST['delivery_phone'];

    $insert_query = "INSERT INTO user_deliveries (user_id, auction_id, delivery_address, delivery_phone, delivery_status)
                     VALUES (?, ?, ?, ?, 'Pending')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iiss", $user_id, $auction_id, $delivery_address, $delivery_phone);
    
    if ($insert_stmt->execute()) {
        $success_message = "Delivery details submitted successfully.";
    } else {
        $error_message = "Failed to submit delivery details. Please try again.";
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $auction_id = intval($_POST['auction_id']);
    $rating = intval($_POST['rating']);
    
    // Basic validation
    if ($rating < 1 || $rating > 5) {
        $review_error = "Please select a valid rating";
    } else {
        $review_text = !empty($_POST['review_text']) ? $_POST['review_text'] : NULL;
        
        $review_query = "INSERT INTO reviews (auction_id, user_id, rating, review)
                         VALUES (?, ?, ?, ?)";
        $review_stmt = $conn->prepare($review_query);
        $review_stmt->bind_param("iiis", $auction_id, $user_id, $rating, $review_text);
        
        if ($review_stmt->execute()) {
            $review_success = "Thank you for your review!";
        } else {
            $review_error = "Failed to submit review. Please try again.";
        }
    }
}

// Fetch auctions for dropdown
$auctions_query = "SELECT a.id, a.item_name 
                   FROM auctions a
                   LEFT JOIN user_deliveries ud ON a.id = ud.auction_id
                   WHERE (a.user_id = ? OR a.winner_id = ?)
                   AND a.status = 'ended'
                   GROUP BY a.id";
$auctions_stmt = $conn->prepare($auctions_query);
$auctions_stmt->bind_param("ii", $user_id, $user_id);
$auctions_stmt->execute();
$auctions_result = $auctions_stmt->get_result();

// Fetch deliveries with review status
$deliveries_query = "SELECT ud.*, a.item_name,
                    (SELECT COUNT(*) FROM reviews r WHERE r.auction_id = a.id AND r.user_id = ?) as has_review
                    FROM user_deliveries ud
                    JOIN auctions a ON ud.auction_id = a.id
                    WHERE ud.user_id = ?
                    ORDER BY ud.created_at DESC";
$deliveries_stmt = $conn->prepare($deliveries_query);
$deliveries_stmt->bind_param("ii", $user_id, $user_id);
$deliveries_stmt->execute();
$deliveries = $deliveries_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Information & Reviews</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1, h2 {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        select, input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        textarea {
            height: 100px;
        }
        
        button {
            background-color:  #0b7dda;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #0b7dda;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .review-btn {
            background-color: #2196F3;
        }
        
        .review-btn:hover {
            background-color: #0b7dda;
        }
        
        .review-btn.disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .rating {
            display: flex;
            margin-bottom: 15px;
        }
        
        .rating input {
            display: none;
        }
        
        .rating label {
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
            padding: 0 5px;
        }
        
        .rating input:checked ~ label {
            color: #ffc107;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        
        .status-completed {
            background-color: #28a745;
            color: #fff;
        }
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #333;
    color: #fff;
    padding: 10px 20px;
}

header nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
}

header nav ul li {
    margin-left: 20px;
}

header nav ul li a {
    color: #fff;
    text-decoration: none;
}

header .auth-buttons button {
    background-color: #393737;
    color: #fff;
    border: none;
    padding: 10px 20px;
    margin-left: 10px;
    cursor: pointer;
}

header .auth-buttons button:hover {
    background-color:rgba(19, 244, 19, 0.712);
}
header {
    background-color: #2a5298;
    padding: 1rem 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.logo img:hover {
    transform: scale(1.05);
    
}

nav ul {
    display: flex;
    list-style: none;
}

nav ul li {
    margin: 0 15px;
    position: relative;
}

nav ul li a {
    color: white;
    text-decoration:none;
    font-weight: 600;
    padding: 5px 0;
    transition: color 0.3s ease;
}

nav ul li a:hover {
    color: rgb(54, 237, 54);
}

nav ul li::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    background: #f8c537;
    bottom: 0;
    left: 0;
    transition: width 0.3s ease;
}

nav ul li:hover::after {
    width: 100%;
}

.auth-buttons {
    display: flex;
    align-items: center;
}

.auth-buttons button {
    background: #4af837;
    color: #1e3c72;
    border: none;
    padding: 8px 20px;
    margin-left: 10px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.auth-buttons button:hover {
    background: #1e3c72;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
}

    </style>
</head>
<body>
<header style="background-color: blue;">
        <div class="logo">
            <img src="img/logo.jpeg" alt="" style="width: 100px; height: 100px; border-radius:50%;">
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
    <div class="container">
        <h1>Delivery Information</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="auction_id">Select Auction Item:</label>
                <select name="auction_id" required>
                    <option value="">-- Select Item --</option>
                    <?php while ($row = $auctions_result->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['item_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="delivery_address">Delivery Address:</label>
                <textarea name="delivery_address" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="delivery_phone">Delivery Phone:</label>
                <input type="text" name="delivery_phone" required>
            </div>
            
            <button type="submit">Submit Delivery Details</button>
        </form>
        
        <h2>Your Delivery History</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($deliveries->num_rows > 0): ?>
                    <?php while ($delivery = $deliveries->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($delivery['item_name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($delivery['delivery_status']) ?>">
                                    <?= htmlspecialchars($delivery['delivery_status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($delivery['created_at'])) ?></td>
                            <td>
                                <button onclick="openReviewModal(<?= $delivery['auction_id'] ?>)" 
                                        class="review-btn <?= $delivery['has_review'] ? 'disabled' : '' ?>"
                                        <?= $delivery['has_review'] ? 'disabled' : '' ?>>
                                    <?= $delivery['has_review'] ? 'Reviewed' : 'Leave Review' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No deliveries found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Submit Your Review</h2>
            
            <?php if (isset($review_success)): ?>
                <div class="message success"><?= htmlspecialchars($review_success) ?></div>
            <?php elseif (isset($review_error)): ?>
                <div class="message error"><?= htmlspecialchars($review_error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="auction_id" id="modalAuctionId">
                
                <div class="form-group">
                    <label>Rating:</label>
                    <div class="rating">
                        <input type="radio" id="star5" name="rating" value="5">
                        <label for="star5">★</label>
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4">★</label>
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3">★</label>
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2">★</label>
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1">★</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Review (Optional):</label>
                    <textarea id="review_text" name="review_text"></textarea>
                </div>
                
                <button type="submit" name="submit_review">Submit Review</button>
            </form>
        </div>
    </div>
    
    <script>
        function openReviewModal(auctionId) {
            document.getElementById('modalAuctionId').value = auctionId;
            document.getElementById('reviewModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('reviewModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html