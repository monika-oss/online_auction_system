<?php
require 'db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Auction</title>
    <link rel="stylesheet" href="styles.css">
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

    <!-- Rest of your homepage content remains the same -->
    <section class="hero" style="background-image: url('img/backdrop1.jpg');">
        <h1>Welcome to U-Deal</h1>
        <p style="font-style:italic; font-size:1.2rem;"><b>Seize the Deal! Steal the Show!</b></p>
        <button onclick="window.location.href='active_bids.php'">Start Bidding</button>
    </section>

    <!-- ... rest of your existing HTML ... -->
    <section class="categories">
        <h2>Categories</h2>
        <div class="category-list">
            <div class="category-item" onclick="filterCategory('Electronics')">
                <img src="img/electronics.jpg" alt="Electronics">
                <p>Electronics</p>
            </div>
            <div class="category-item" onclick="filterCategory('Luxury Fashion')">
                <img src="img/luxuryfashion.jpg" alt="Fashion">
                <p>Luxury Fashion</p>
            </div>
            <div class="category-item" onclick="filterCategory('Art')">
                <img src="img/art.jpg" alt="Art">
                <p>Art</p>
            </div>
            <div class="category-item" onclick="filterCategory('Furniture')">
                <img src="img/furniture.jpg" alt="Furniture">
                <p>Furniture</p>
            </div>
            <div class="category-item" onclick="filterCategory('Vehicles')">
                <img src="img/vehicles.jpg" alt="Vehicles">
                <p>Vehicles</p>
            </div>
            <div class="category-item" onclick="filterCategory('Pets')">
                <img src="img/pets.jpeg" alt="Pets">
                <p>Pets</p>
            </div>
        </div>
    </section>
    
 <footer>
        <p>&copy; 2025 Online Auction. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>
</body>
</html>