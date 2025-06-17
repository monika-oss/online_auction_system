<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT username, email, full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Query to get user's payment history
$payment_history = "SELECT a.item_name, a.current_bid, a.end_date
                   FROM auctions a
                   WHERE a.winner_id = ?
                   ORDER BY a.end_date DESC";
$stmt = $conn->prepare($payment_history);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - U-Deal Auction</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-change-password {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-change-password:hover {
            background-color: #27ae60;
        }
        
        .btn-logout {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-logout:hover {
            background-color: #c0392b;
        }
        .btn-home {
            background-color:rgb(60, 180, 231);
            color: white;
        }
        
        .btn-home:hover {
            background-color: rgb(60, 180, 231);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Your Profile</h1>
        
        <div class="profile-info">
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
        </div>
        
        <div class="actions">
            <button class="btn btn-edit" onclick="window.location.href='edit-profile.php'">Edit Profile</button>
            <button class="btn btn-change-password" onclick="window.location.href='change-password.php'">Change Password</button>
            <button class="btn btn-logout" onclick="window.location.href='logout.php'">Logout</button>
            <button class="btn btn-home" onclick="window.location.href='index.php'">Go to Home</button>

        </div>
    </div>
</body>
</html>