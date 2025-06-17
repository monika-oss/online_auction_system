<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has membership
if (!isset($_SESSION['has_membership'])) {
    header("Location: membership.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bidding - Online Auction</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .bidding-form {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .bidding-form h2 {
            text-align: center;
            margin-bottom: 20px;
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
        
        .form-group input, 
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 100px;
        }
        
        .form-submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .form-submit:hover {
            background-color: #0056b3;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
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
    </header>

    <div class="bidding-form">
        <h2>Create New Auction</h2>
        <form id="createBiddingForm" action="process_bidding.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Luxury Fashion">Luxury Fashion</option>
                    <option value="Art">Art</option>
                    <option value="Furniture">Furniture</option>
                    <option value="Vehicles">Vehicles</option>
                    <option value="Pets">Pets</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Item Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="starting_bid">Starting Bid (Rs.)</label>
                <input type="number" id="starting_bid" name="starting_bid" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="bid_increment">Bid Increment (Rs.)</label>
                <input type="number" id="bid_increment" name="bid_increment" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="end_date">Auction End Date & Time</label>
                <input type="datetime-local" id="end_date" name="end_date" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="item_image">Item Image</label>
                <input type="file" id="item_image" name="item_image" accept="image/*" required onchange="previewImage(event)">
                <img id="preview" class="preview-image" style="display: none;">
            </div>
            
            <button type="submit" class="form-submit">Create Auction</button>
        </form>
    </div>
    
    <footer>
        <p>&copy; 2025 Online Auction. All rights reserved.</p>
    </footer>
    
    <script>
        // Image preview function
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Form validation
        document.getElementById('createBiddingForm').addEventListener('submit', function(e) {
            const endDate = new Date(document.getElementById('end_date').value);
            const now = new Date();
            
            if (endDate <= now) {
                alert('Auction end date must be in the future');
                e.preventDefault();
            }
        });
    </script>
    
</body>
</html>