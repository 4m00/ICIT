<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$success_message = $error_message = '';

// Database connection
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Process role change if form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $new_role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
        
        if ($user_id && in_array($new_role, ['student', 'teacher', 'admin'])) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $success_message = "User role updated successfully!";
        } else {
            $error_message = "Invalid input data.";
        }
    }
    
    // Get all users
    $stmt = $db->query("SELECT id, name, email, role, department, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get login attempts for security monitoring
    $stmt = $db->query("
        SELECT la.*, u.name 
        FROM login_attempts la
        LEFT JOIN users u ON la.email = u.email
        ORDER BY la.attempt_time DESC
        LIMIT 50
    ");
    $login_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Virtual Dean's Office</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="dashboard-header">
                <h1>Admin Panel</h1>
                <p>Manage users and system settings</p>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>
            
            <!-- Tabbed interface for admin sections -->
            <div class="admin-tabs">
                <button class="tab-btn active" data-tab="users">Users Management</button>
                <button class="tab-btn" data-tab="security">Security Log</button>
            </div>
            
            <!-- Users Management Tab -->
            <section id="users" class="tab-content active">
                <h2>User Management</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="role-badge role-<?= $user['role'] ?>">
                                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['department']) ?></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm" onclick="openRoleModal(<?= $user['id'] ?>, '<?= $user['name'] ?>', '<?= $user['role'] ?>')">
                                            Change Role
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Security Log Tab -->
            <section id="security" class="tab-content">
                <h2>Security Log</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Email</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($login_attempts as $attempt): ?>
                                <tr class="<?= $attempt['success'] ? 'success-row' : 'failure-row' ?>">
                                    <td><?= date('M j, Y H:i:s', strtotime($attempt['attempt_time'])) ?></td>
                                    <td><?= htmlspecialchars($attempt['email']) ?></td>
                                    <td><?= htmlspecialchars($attempt['name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($attempt['ip_address']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $attempt['success'] ? 'success' : 'failure' ?>">
                                            <?= $attempt['success'] ? 'Success' : 'Failed' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
        
        <?php include 'includes/footer.php'; ?>
    </div>
    
    <!-- Modal for changing user role -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Change User Role</h2>
            <p>Changing role for: <span id="modalUserName"></span></p>
            
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="role-form">
                <input type="hidden" id="modalUserId" name="user_id" value="">
                
                <div class="form-group">
                    <label for="role">New Role:</label>
                    <select id="modalUserRole" name="role" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary modal-cancel">Cancel</button>
                    <button type="submit" name="update_role" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>