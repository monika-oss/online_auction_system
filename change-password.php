<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if ($update_stmt->execute()) {
                $success = "Password changed successfully!";
                // Clear form fields
                $_POST = array();
            } else {
                $error = "Something went wrong. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - U-Deal Auction</title>
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
            max-width: 500px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .success {
            color: #2ecc71;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 14px;
        }
        
        .strength-weak {
            color: #e74c3c;
        }
        
        .strength-medium {
            color: #f39c12;
        }
        
        .strength-strong {
            color: #2ecc71;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Change Password</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form id="changePasswordForm" action="change-password.php" method="post">
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" id="new_password" name="new_password" required>
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div id="password-match" class="password-strength"></div>
            </div>
            
            <button type="submit" class="btn">Change Password</button>
        </form>
        
        <div class="back-link">
            <a href="profile.php">← Back to Profile</a>
        </div>
    </div>

    <script>
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthText = document.getElementById('password-strength');
        const matchText = document.getElementById('password-match');
        
        newPassword.addEventListener('input', checkPasswordStrength);
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordStrength() {
            const value = newPassword.value;
            let strength = 0;
            
            // Check length
            if (value.length >= 8) strength++;
            
            // Check for uppercase letters
            if (/[A-Z]/.test(value)) strength++;
            
            // Check for numbers
            if (/[0-9]/.test(value)) strength++;
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(value)) strength++;
            
            // Update strength text
            if (value.length === 0) {
                strengthText.textContent = '';
                strengthText.className = 'password-strength';
            } else if (strength <= 2) {
                strengthText.textContent = 'Weak';
                strengthText.className = 'password-strength strength-weak';
            } else if (strength === 3) {
                strengthText.textContent = 'Medium';
                strengthText.className = 'password-strength strength-medium';
            } else {
                strengthText.textContent = 'Strong';
                strengthText.className = 'password-strength strength-strong';
            }
        }
        
        function checkPasswordMatch() {
            if (confirmPassword.value.length === 0) {
                matchText.textContent = '';
                matchText.className = 'password-strength';
            } else if (newPassword.value === confirmPassword.value) {
                matchText.textContent = 'Passwords match';
                matchText.className = 'password-strength strength-strong';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'password-strength strength-weak';
            }
        }
        
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value.trim();
            const newPasswordVal = newPassword.value.trim();
            const confirmPasswordVal = confirmPassword.value.trim();
            
            if (!currentPassword || !newPasswordVal || !confirmPasswordVal) {
                e.preventDefault();
                alert('Please fill all fields.');
                return false;
            }
            
            if (newPasswordVal !== confirmPasswordVal) {
                e.preventDefault();
                alert('New passwords do not match.');
                return false;
            }
            
            if (newPasswordVal.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>