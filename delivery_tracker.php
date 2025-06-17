<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auction_id = intval($_POST['auction_id']);
    $delivery_address = $_POST['delivery_address'];
    $delivery_phone = $_POST['delivery_phone'];

    // Insert delivery details
    $insert_query = "INSERT INTO user_deliveries (user_id, auction_id, delivery_address, delivery_phone)
                     VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iiss", $user_id, $auction_id, $delivery_address, $delivery_phone);
    
    if ($insert_stmt->execute()) {
        $success_message = "Delivery details submitted successfully.";
    } else {
        $error_message = "Failed to submit delivery details. Please try again.";
    }
}

// Fetch auctions for the user to display in the dropdown
$query = "SELECT id, item_name FROM auctions WHERE user_id = ? AND status = 'ended'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the user's deliveries
$deliveries_query = "SELECT ud.id, a.item_name, ud.delivery_address, ud.delivery_phone, ud.created_at, ud.delivery_status 
                     FROM user_deliveries ud
                     JOIN auctions a ON ud.auction_id = a.id
                     WHERE ud.user_id = ?";
$deliveries_stmt = $conn->prepare($deliveries_query);
$deliveries_stmt->bind_param("i", $user_id);
$deliveries_stmt->execute();
$deliveries_result = $deliveries_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Delivery Form and Tracker</title>
    <style>
    body {
    font-family: 'Arial', sans-serif;
    background-color: #e9ecef;
    color: #495057;
    margin: 0;
    padding: 0;
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
h2 {
    color: #007bff;
    text-align: center;
    margin-bottom: 20px;
    font-size: 2em;
    font-weight: 700;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

th {
    background-color: #007bff;
    color: white;
    font-size: 1.1em;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

tr:hover {
    background-color: #e2e6ea;
    transform: scale(1.01);
    transition: all 0.2s ease-in-out;
}

td {
    background-color: white;
}

td:hover {
    background-color: #f8f9fa;
}

.success-message {
    color: green;
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
}

.error-message {
    color: red;
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
}

@media (max-width: 600px) {
    body {
        padding: 10px;
    }

    table, th, td {
        font-size: 14px;
    }

    th, td {
        padding: 10px;
    }
}

/* Button Styling */
button {
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    font-size: 1em;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.3s;
}

button:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}

/* Add a container for the whole content */
.container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        <h2>Your Deliveries:</h2>
        <?php if ($deliveries_result->num_rows > 0): ?>
            <?php while ($delivery = $deliveries_result->fetch_assoc()): ?>
                <div class="delivery-card">
                    <div class="delivery-header">
                        <h3><?php echo htmlspecialchars($delivery['item_name']); ?></h3>
                        <div class="delivery-status"><b><p>Delivery Status:</b>
                        <?php echo htmlspecialchars($delivery['delivery_status']); ?></p></div>
                    </div>
                    <div class="delivery-details">
                        <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($delivery['delivery_address']); ?></p>
                        <p><strong>Delivery Phone:</strong> <?php echo htmlspecialchars($delivery['delivery_phone']); ?></p>
                        <p><strong>Created At:</strong> <?php echo htmlspecialchars($delivery['created_at']); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No deliveries found.</p>
        <?php endif; ?>
    </div>
</body>
</html>