<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$name = $email = $password = $department = "";
$errors = [];

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize inputs
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $department = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING));
    
    // Validate inputs
    if (empty($name)) {
        $errors['name'] = "Name is required";
    } elseif (strlen($name) < 3) {
        $errors['name'] = "Name must be at least 3 characters";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    if (empty($department)) {
        $errors['department'] = "Department is required";
    }
    
    // If no validation errors, try to register user
    if (empty($errors)) {
        try {
            $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors['email'] = "Email already in use";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user (with 'student' as default role)
                $stmt = $db->prepare("INSERT INTO users (name, email, password, department, role, created_at) VALUES (?, ?, ?, ?, 'student', NOW())");
                $stmt->execute([$name, $email, $hashed_password, $department]);
                
                // Registration successful - redirect to login
                $_SESSION['register_success'] = "Registration successful! You can now login.";
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors['db'] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Virtual Dean's Office</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Register</h1>
                <p>Create your Virtual Dean's Office account</p>
            </div>
            
            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?= $errors['db'] ?></div>
            <?php endif; ?>
            
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="auth-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?= htmlspecialchars($name); ?>"
                        class="<?= !empty($errors['name']) ? 'is-invalid' : ''; ?>"
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="invalid-feedback"><?= $errors['name']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($email); ?>"
                        class="<?= !empty($errors['email']) ? 'is-invalid' : ''; ?>"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback"><?= $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        class="<?= !empty($errors['password']) ? 'is-invalid' : ''; ?>"
                    >
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback"><?= $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password"
                        class="<?= !empty($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                    >
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="department">Department</label>
                    <select 
                        id="department" 
                        name="department"
                        class="<?= !empty($errors['department']) ? 'is-invalid' : ''; ?>"
                    >
                        <option value="" <?= empty($department) ? 'selected' : ''; ?>>Select Department</option>
                        <option value="Computer Science" <?= $department === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Mathematics" <?= $department === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                        <option value="Physics" <?= $department === 'Physics' ? 'selected' : ''; ?>>Physics</option>
                        <option value="Chemistry" <?= $department === 'Chemistry' ? 'selected' : ''; ?>>Chemistry</option>
                        <option value="Biology" <?= $department === 'Biology' ? 'selected' : ''; ?>>Biology</option>
                        <option value="Economics" <?= $department === 'Economics' ? 'selected' : ''; ?>>Economics</option>
                        <option value="Literature" <?= $department === 'Literature' ? 'selected' : ''; ?>>Literature</option>
                    </select>
                    <?php if (!empty($errors['department'])): ?>
                        <div class="invalid-feedback"><?= $errors['department']; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login</a></p>
                <a href="index.php" class="back-link">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>