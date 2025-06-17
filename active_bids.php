<?php
require 'db.php';
session_start();

// Check if user is logged in (optional for this page)
if (!isset($_SESSION['user_id'])) {
    // Not redirecting since guests should be able to view auctions
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

// Get category filter if set
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build query for active auctions
$query = "SELECT a.*, u.username FROM auctions a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.status = 'active' AND a.end_date > NOW()";

if (!empty($category_filter)) {
    $query .= " AND a.category = '$category_filter'";
}

if (!empty($search_query)) {
    $query .= " AND (a.item_name LIKE '%$search_query%' OR a.description LIKE '%$search_query%')";
}

$query .= " ORDER BY a.end_date ASC";

$result = $conn->query($query);

// Get all categories for the filter dropdown
$categories_result = $conn->query("SELECT DISTINCT category FROM auctions WHERE status = 'active' AND end_date > NOW() ORDER BY category");
$categories = [];
if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Bidding - Online Auction</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .live-bidding-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .filter-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .category-filter, .search-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-filter select, .search-filter input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-filter button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .auction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .auction-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .auction-card:hover {
            transform: translateY(-5px);
        }
        
        .auction-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .auction-details {
            padding: 15px;
        }
        
        .auction-title {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .auction-category {
            display: inline-block;
            background-color: #f0f0f0;
            color: #666;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .bid-info {
            margin: 10px 0;
            font-size: 14px;
        }
        
        .current-bid {
            font-weight: bold;
            color: #007bff;
            font-size: 18px;
        }
        
        .bid-ends {
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }
        
        .bid-button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .bid-button:hover {
            background-color: #0056b3;
        }
        
        .seller-info {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .no-auctions {
            text-align: center;
            grid-column: 1 / -1;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }
        
        .time-remaining {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .highlight-card {
            border: 2px solid #007bff;
        }
        
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
            }
            
            .category-filter, .search-filter {
                width: 100%;
            }
            
            .auction-grid {
                grid-template-columns: 1fr;
            }
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

    <div class="live-bidding-container">
        <h1 class="page-title">Active Live Auctions</h1>
        
        <div class="filter-section">
            <form class="category-filter" method="get">
                <label for="category">Filter by Category:</label>
                <select name="category" id="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($category_filter)): ?>
                    <a href="active_bids.php" class="reset-filter">Reset</a>
                <?php endif; ?>
            </form>
            
            <form class="search-filter" method="get">
                <?php if (!empty($category_filter)): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <div class="auction-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($auction = $result->fetch_assoc()): 
                    // Calculate time remaining
                    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                    $end_date = new DateTime($auction['end_date'], new DateTimeZone('Asia/Kolkata'));
                    $interval = $now->diff($end_date);
                    $seconds_remaining = $end_date->getTimestamp() - $now->getTimestamp();
                    
                    if ($seconds_remaining <= 0) {
                        $time_left = "Ended";
                        $highlight = false;
                    } else {
                        $hours = floor($seconds_remaining / 3600);
                        $minutes = floor(($seconds_remaining % 3600) / 60);
                        $seconds = $seconds_remaining % 60;
                        
                        if ($hours > 24) {
                            $days = floor($hours / 24);
                            $hours = $hours % 24;
                            $time_left = sprintf("%dd %02dh %02dm", $days, $hours, $minutes);
                        } elseif ($hours > 0) {
                            $time_left = sprintf("%02dh %02dm %02ds", $hours, $minutes, $seconds);
                        } else {
                            $time_left = sprintf("%02dm %02ds", $minutes, $seconds);
                        }
                        
                        $highlight = ($seconds_remaining < 3600); // Highlight if less than 1 hour remaining
                    }
                    
                    // Get bid count
                    $bid_count_query = "SELECT COUNT(*) as count FROM bids WHERE auction_id = ?";
                    $bid_stmt = $conn->prepare($bid_count_query);
                    $bid_stmt->bind_param("i", $auction['id']);
                    $bid_stmt->execute();
                    $bid_count_result = $bid_stmt->get_result();
                    $bid_count = $bid_count_result->fetch_assoc()['count'];
                    $bid_stmt->close();
                ?>
                    <div class="auction-card <?php echo $highlight ? 'highlight-card' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($auction['image_path']); ?>" alt="<?php echo htmlspecialchars($auction['item_name']); ?>" class="auction-image">
                        <div class="auction-details">
                            <h3 class="auction-title"><?php echo htmlspecialchars($auction['item_name']); ?></h3>
                            <span class="auction-category"><?php echo htmlspecialchars($auction['category']); ?></span>
                            
                            <div class="bid-info">
                                <div class="starting-bid">Rs. <?php echo number_format($auction['starting_bid'], 2); ?></div>
                                <div class="current-bid" id="current-bid-<?php echo $auction['id']; ?>">Rs. <?php echo number_format($auction['current_bid'], 2); ?></div>
                                <div class="bid-ends" id="bid-ends-<?php echo $auction['id']; ?>">
                                    Bids: <span id="bidCount-<?php echo $auction['id']; ?>"><?php echo $bid_count; ?></span> | 
                                    Ends in: <span class="time-remaining"><?php echo $time_left; ?></span>
                                </div>
                            </div>
                            
                            <a href="place_bid.php?id=<?php echo $auction['id']; ?>" class="bid-button">Place Bid</a>
                            
                            <div class="seller-info">
                                Listed by: <?php echo htmlspecialchars($auction['username']); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-auctions">
                    <p>No active auctions found<?php echo !empty($category_filter) ? ' in this category' : ''; ?>.</p>
                    <a href="create_bidding1.php" class="bid-button" style="display: inline-block; width: auto; padding: 10px 20px; margin-top: 20px;">
                        Create a New Auction
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Online Auction. All rights reserved.</p>
    </footer>
    
    <script>
        // Function to update all countdown timers
        function updateCountdowns() {
            const timers = document.querySelectorAll('.time-remaining');
            const now = Math.floor(Date.now() / 1000); // Current timestamp in seconds
            
            timers.forEach(timer => {
                const endTime = timer.parentElement.getAttribute('data-end');
                if (!endTime) return;
                
                const endTimestamp = Math.floor(new Date(endTime).getTime() / 1000);
                let secondsRemaining = endTimestamp - now;
                
                if (secondsRemaining <= 0) {
                    timer.textContent = "Ended";
                    const bidButton = timer.closest('.auction-card')?.querySelector('.bid-button');
                    if (bidButton) {
                        bidButton.textContent = "View Auction";
                        bidButton.style.backgroundColor = "#6c757d";
                    }
                    return;
                }
                
                // Calculate time units
                const hours = Math.floor(secondsRemaining / 3600);
                const minutes = Math.floor((secondsRemaining % 3600) / 60);
                const seconds = secondsRemaining % 60;
                
                // Format the display
                let timeString;
                if (secondsRemaining > 86400) { // More than 1 day
                    const days = Math.floor(hours / 24);
                    const remainingHours = hours % 24;
                    timeString = ${days}d ${remainingHours}h ${minutes}m;
                } else if (secondsRemaining > 3600) { // More than 1 hour
                    timeString = ${hours}h ${minutes}m ${seconds}s;
                } else {
                    timeString = ${minutes}m ${seconds}s;
                }
                
                timer.textContent = timeString;
            });
        }

        // Update countdowns every second
        setInterval(updateCountdowns, 1000);

        // Initialize immediately
        updateCountdowns();

        // Auto-refresh page when all auctions have ended (optional)
        function checkAllEnded() {
            const activeTimers = Array.from(document.querySelectorAll('.time-remaining')).filter(
                timer => !timer.textContent.includes("Ended")
            );
            if (activeTimers.length === 0) {
                setTimeout(() => location.reload(), 15000); // Refresh after 15 seconds if all ended
            }
        }

        // Check every minute
        setInterval(checkAllEnded, 60000);
        
        // Periodically check for new bids (every 30 seconds)
        function fetchBidUpdates() {
            // Get all auction IDs on the page
            const auctionIds = Array.from(document.querySelectorAll('.auction-card')).map(card => {
                const href = card.querySelector('a.bid-button')?.href;
                return href ? href.split('id=')[1] : null;
            }).filter(id => id !== null);
            
            if (auctionIds.length === 0) return;
            
            // Request current bid counts from server
            fetch('get_bid_counts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({auctionIds: auctionIds})
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                // Update bid counts on page
                data.forEach(auction => {
                    const bidElement = document.getElementById(bidCount-${auction.id});
                    if (bidElement) {
                        bidElement.textContent = auction.count;
                    }
                    
                    const currentBidElement = document.getElementById(current-bid-${auction.id});
                    if (currentBidElement && auction.current_bid) {
                        currentBidElement.textContent = Rs. ${parseFloat(auction.current_bid).toFixed(2)};
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching bid updates:', error);
            })
            .finally(() => {
                // Schedule next update
                setTimeout(fetchBidUpdates, 30000);
            });
        }

        // Start the bid updates
        setTimeout(fetchBidUpdates, 30000);
    </script>
</body>
</html>
<?php
$conn->close();
?>