<?php
session_start();
require_once 'config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Virtual Dean's Office</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Forgot Password</h1>
                <p>Reset your account password</p>
            </div>
            
            <div class="alert alert-info">
                <p>This is a demo feature. In a real application, this would send a password reset link to your email.</p>
            </div>
            
            <form action="#" method="post" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
            
            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Login</a></p>
                <a href="index.php" class="back-link">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>