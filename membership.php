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
session_start(); // Start the session to use user session 

function getPlanDetails($plan) {
    switch ($plan) {
        case '3months':
            return ["price" => "Rs.299", "qr_image" => "img/3monthplan.jpg"];
        case '6months':
            return ["price" => "Rs.459", "qr_image" => "img/6monthplan.jpg"];
        case '1year':
            return ["price" => "Rs.799", "qr_image" => "img/1yearplan.jpg"];
    }
}
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $plan = $_POST['membership_plan'];
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO memberships (user_id, plan) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $plan);
    
    if ($stmt->execute()) {
        // Store membership status in session
        $_SESSION['has_membership'] = true;
        $_SESSION['membership_plan'] = $plan;
        
        // Redirect to create bidding page
        header("Location: create_bidding.php");
        exit();
    } else {
        $error = "Error occurred: " . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans - Online Auction</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
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
    </header>

    <section class="membership-plans">
        <h1>Choose Your Membership Plan</h1>
        <div class="plans-container">
            <!-- 3-Month Plan -->
            <div class="plan-card">
                <h2>Basic Plan</h2>
                <p class="price">Rs.299</p>
                <p>Access to all the auction</p>
                <p>Priority customer support</p><br>
                <button onclick="selectPlan('3months')">Select Plan</button>
            </div>

            <!-- 6-Month Plan -->
            <div class="plan-card">
                <h2>Premium Plan</h2>
                <p class="price">Rs.459</p>
                <p>Access to all the auction</p>
                <p>Priority customer support</p>
                <p>Free Shipping</p><br>
                <button onclick="selectPlan('6months')">Select Plan</button>
            </div>

            <!-- 1-Year Plan -->
            <div class="plan-card">
                <h2>VIP plan</h2>
                <p class="price">Rs.799</p>
                <p>Access to all the auction</p>
                <p>Priority customer support</p>
                <p>Free Shipping</p>
                <p>20% Cashback on your 1st Membership</p><br>
                <button onclick="selectPlan('1year')">Select Plan</button>
            </div>
        </div>
        
        <div id="payment-section" style="display: none;">
            <h2 id="selected-plan-title"></h2>
            <p id="plan-price"></p>
            <img id="qr-code" src="" alt="QR Code" width="200">
            <p>Scan the QR code for making payment</p>
            <form id="payment-form" method="POST">
                <input type="hidden" name="membership_plan" id="membership-plan-input">
                <button type="submit">Done</button>
            </form>
        </div>
    </section>
    
    <footer>
        <p>&copy; 2025 Online Auction. All rights reserved.</p>
    </footer>
    
    <script>
        function selectPlan(plan) {
            const planDetails = {
                '3months': { title: "3 Months", price: "Rs.299", qr: "img/3monthplan.jpg" },
                '6months': { title: "6 Months", price: "Rs.459", qr: "img/6monthplan.jpg" },
                '1year': { title: "1 Year", price: "Rs.799", qr: "img/1yearplan.jpg" }
            };

            document.getElementById("selected-plan-title").innerText = planDetails[plan].title;
            document.getElementById("plan-price").innerText = planDetails[plan].price;
            document.getElementById("qr-code").src = planDetails[plan].qr;
            document.getElementById("membership-plan-input").value = plan;
            document.getElementById("payment-section").style.display = "block";
            document.getElementById("payment-section").scrollIntoView({ behavior: 'smooth' });
        }
        
    </script>
    
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // User ID would be retrieved from your session or authentication
        $userId = $_SESSION['user_id'];
        $plan = $_POST['membership_plan'];

        // Prepare an SQL statement
        $stmt = $conn->prepare("INSERT INTO memberships (user_id, plan) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $plan);

        // Execute the query
        if ($stmt->execute()) {
            echo "<script>alert('Payment successful, membership activated!');</script>";
        } else {
            echo "<script>alert('Error occurred: " . $conn->error . "');</script>";
        }

        $stmt->close();
    }
    
    
    ?>
</body>
</html>