<?php
session_start();
// At the top of your PHP scripts (process_bidding.php and place_bid.php)
date_default_timezone_set('Asia/Kolkata'); // Set your appropriate timezone

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "auction_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $starting_bid = floatval($_POST['starting_bid']);
    $bid_increment = floatval($_POST['bid_increment']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    $user_id = $_SESSION['user_id'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $unique_filename;
        
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            die("Error uploading image");
        }
    }
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO auctions (user_id, item_name, category, description, starting_bid, bid_increment, end_date, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssddss", $user_id, $item_name, $category, $description, $starting_bid, $bid_increment, $end_date, $image_path);
    
    if ($stmt->execute()) {
        header("Location: success.php?message=auction_created");
        exit();
    } else {
        die("Error: " . $conn->error);
    }
    
    $stmt->close();
}
$conn->close();
?>