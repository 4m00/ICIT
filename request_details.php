<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get request ID from URL
$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$requestId) {
    header('Location: dashboard.php');
    exit;
}

// Database connection
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get request details
    $stmt = $db->prepare("
        SELECT r.*, 
               s.status_name, 
               t.type_name,
               u.name as student_name,
               u.email as student_email,
               u.department as student_department
        FROM requests r
        JOIN request_status s ON r.status_id = s.id
        JOIN request_types t ON r.type = t.id
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if request exists
    if (!$request) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Check if user has permission to view this request
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    
    // Students can only view their own requests
    if ($userRole === 'student' && $request['user_id'] != $userId) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Get request history/comments
    $stmt = $db->prepare("
        SELECT rh.*, 
               u.name as user_name,
               u.role as user_role
        FROM request_history rh
        JOIN users u ON rh.user_id = u.id
        WHERE rh.request_id = ?
        ORDER BY rh.created_at ASC
    ");
    $stmt->execute([$requestId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
        $comment = trim(filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING));
        
        if (!empty($comment)) {
            // Add comment to request history
            $stmt = $db->prepare("
                INSERT INTO request_history 
                (request_id, user_id, action, comment, created_at)
                VALUES (?, ?, 'comment', ?, NOW())
            ");
            $stmt->execute([$requestId, $userId, $comment]);
            
            // Redirect to refresh the page
            header("Location: request_details.php?id=$requestId");
            exit;
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details - Virtual Dean's Office</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="dashboard-header">
                <div class="back-navigation">
                    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
                </div>
                <h1>Request Details</h1>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php else: ?>
                <div class="request-details-container">
                    <div class="request-main-info">
                        <div class="request-header">
                            <h2><?= htmlspecialchars($request['title']); ?></h2>
                            <span class="request-status status-<?= strtolower($request['status_name']); ?>">
                                <?= htmlspecialchars($request['status_name']); ?>
                            </span>
                        </div>
                        
                        <div class="request-meta">
                            <div class="meta-item">
                                <span class="meta-label">Type:</span>
                                <span class="meta-value"><?= htmlspecialchars($request['type_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Submitted:</span>
                                <span class="meta-value"><?= date('M j, Y H:i', strtotime($request['created_at'])); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Last Updated:</span>
                                <span class="meta-value">
                                    <?= $request['updated_at'] ? date('M j, Y H:i', strtotime($request['updated_at'])) : 'Not updated yet'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="request-student-info">
                            <h3>Student Information</h3>
                            <div class="student-details">
                                <div class="detail-item">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value"><?= htmlspecialchars($request['student_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?= htmlspecialchars($request['student_email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Department:</span>
                                    <span class="detail-value"><?= htmlspecialchars($request['student_department']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="request-description">
                            <h3>Request Description</h3>
                            <div class="description-content">
                                <?= nl2br(htmlspecialchars($request['description'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($userRole === 'teacher' || $userRole === 'admin'): ?>
                            <?php if ($request['status_name'] === 'Pending'): ?>
                                <div class="request-actions">
                                    <h3>Process Request</h3>
                                    <div class="action-buttons">
                                        <a href="process_request.php?id=<?= $request['id']; ?>&action=approve" class="btn btn-success">
                                            Approve Request
                                        </a>
                                        <a href="process_request.php?id=<?= $request['id']; ?>&action=reject" class="btn btn-danger">
                                            Reject Request
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="request-activity">
                        <h3>Request Activity</h3>
                        
                        <div class="activity-timeline">
                            <?php if (empty($history)): ?>
                                <div class="empty-timeline">
                                    <p>No activity recorded yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($history as $item): ?>
                                    <div class="timeline-item action-<?= $item['action']; ?>">
                                        <div class="timeline-icon"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-header">
                                                <span class="timeline-user">
                                                    <?= htmlspecialchars($item['user_name']); ?> 
                                                    (<?= ucfirst(htmlspecialchars($item['user_role'])); ?>)
                                                </span>
                                                <span class="timeline-date">
                                                    <?= date('M j, Y H:i', strtotime($item['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="timeline-body">
                                                <?php if ($item['action'] === 'status_change'): ?>
                                                    <p>Changed request status to <strong><?= htmlspecialchars($item['new_value']); ?></strong></p>
                                                <?php elseif ($item['action'] === 'comment'): ?>
                                                    <p><?= nl2br(htmlspecialchars($item['comment'])); ?></p>
                                                <?php else: ?>
                                                    <p><?= htmlspecialchars($item['action']); ?>: <?= htmlspecialchars($item['comment']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="add-comment">
                            <h4>Add Comment</h4>
                            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $requestId); ?>" method="post">
                                <div class="form-group">
                                    <textarea 
                                        name="comment" 
                                        rows="3" 
                                        placeholder="Add your comment here..."
                                        required
                                    ></textarea>
                                </div>
                                <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>