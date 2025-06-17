<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 20px;
            border-radius: 5px;
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="success-message">
        <h1>Success!</h1>
        <?php if (isset($_GET['message']) && $_GET['message'] == 'auction_created'): ?>
            <p>Your auction has been created successfully!</p>
            <a href="index.php">Return to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>