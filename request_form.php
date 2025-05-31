<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$title = $description = $type = "";
$errors = [];
$success = false;

// Process request form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize inputs
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING));
    
    // Validate inputs
    if (empty($title)) {
        $errors['title'] = "Title is required";
    } elseif (strlen($title) < 5) {
        $errors['title'] = "Title must be at least 5 characters";
    }
    
    if (empty($description)) {
        $errors['description'] = "Description is required";
    } elseif (strlen($description) < 10) {
        $errors['description'] = "Description must be at least 10 characters";
    }
    
    if (empty($type)) {
        $errors['type'] = "Request type is required";
    }
    
    // If no validation errors, try to save the request
    if (empty($errors)) {
        try {
            $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Insert new request (status_id 1 = Pending)
            $stmt = $db->prepare("
                INSERT INTO requests 
                (user_id, title, description, type, status_id, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $type
            ]);
            
            // Request created successfully
            $success = true;
            $title = $description = $type = ""; // Clear form
            
        } catch (PDOException $e) {
            $errors['db'] = "Database error: " . $e->getMessage();
        }
    }
}

// Get request types for dropdown
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->query("SELECT id, type_name FROM request_types ORDER BY type_name");
    $request_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errors['db'] = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Request - Virtual Dean's Office</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="dashboard-header">
                <h1>Create New Request</h1>
                <p>Submit a new academic request</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Your request has been submitted successfully!</p>
                    <a href="dashboard.php" class="btn btn-sm">Back to Dashboard</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?= $errors['db'] ?></div>
            <?php endif; ?>
            
            <div class="form-card">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="request-form">
                    <div class="form-group">
                        <label for="title">Request Title</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            value="<?= htmlspecialchars($title); ?>"
                            placeholder="e.g., Course Registration Request"
                            class="<?= !empty($errors['title']) ? 'is-invalid' : ''; ?>"
                        >
                        <?php if (!empty($errors['title'])): ?>
                            <div class="invalid-feedback"><?= $errors['title']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Request Type</label>
                        <select 
                            id="type" 
                            name="type"
                            class="<?= !empty($errors['type']) ? 'is-invalid' : ''; ?>"
                        >
                            <option value="">Select Request Type</option>
                            <?php foreach ($request_types as $req_type): ?>
                                <option value="<?= $req_type['id']; ?>" <?= $type == $req_type['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($req_type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['type'])): ?>
                            <div class="invalid-feedback"><?= $errors['type']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="6"
                            placeholder="Provide details about your request..."
                            class="<?= !empty($errors['description']) ? 'is-invalid' : ''; ?>"
                        ><?= htmlspecialchars($description); ?></textarea>
                        <?php if (!empty($errors['description'])): ?>
                            <div class="invalid-feedback"><?= $errors['description']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </main>
        
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>