<?php
require_once 'config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Получаем все уведомления пользователя
$sql = "SELECT n.*, 
               CASE 
                   WHEN n.created_at >= NOW() - INTERVAL 1 HOUR THEN 'только что'
                   WHEN n.created_at >= NOW() - INTERVAL 1 DAY THEN 'сегодня'
                   WHEN n.created_at >= NOW() - INTERVAL 2 DAY THEN 'вчера'
                   ELSE DATE_FORMAT(n.created_at, '%d.%m.%Y')
               END as relative_time
        FROM notifications n 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 50";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $conn->error);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Получаем количество непрочитанных уведомлений
$sql = "SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread = $result->fetch_assoc();

// Если это AJAX запрос на отметку уведомления как прочитанное
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    
    $sql = "UPDATE notifications 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $notification_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// Если это AJAX запрос на отметку всех уведомлений как прочитанных
if (isset($_POST['mark_all_read'])) {
    $sql = "UPDATE notifications 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// Группируем уведомления по дате
$grouped_notifications = [];
foreach ($notifications as $notification) {
    $date = $notification['relative_time'];
    if (!isset($grouped_notifications[$date])) {
        $grouped_notifications[$date] = [];
    }
    $grouped_notifications[$date][] = $notification;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уведомления - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .notification-card:hover {
            transform: translateX(5px);
        }
        .notification-card.unread {
            background-color: rgba(var(--bs-primary-rgb), 0.05);
            border-left-color: var(--bs-primary);
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .notification-type-info .notification-icon {
            background-color: rgba(var(--bs-info-rgb), 0.1);
            color: var(--bs-info);
        }
        .notification-type-warning .notification-icon {
            background-color: rgba(var(--bs-warning-rgb), 0.1);
            color: var(--bs-warning);
        }
        .notification-type-error .notification-icon {
            background-color: rgba(var(--bs-danger-rgb), 0.1);
            color: var(--bs-danger);
        }
        .notification-type-success .notification-icon {
            background-color: rgba(var(--bs-success-rgb), 0.1);
            color: var(--bs-success);
        }
        .date-divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }
        .date-divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #dee2e6;
            z-index: 1;
        }
        .date-divider span {
            background: #fff;
            padding: 0 1rem;
            color: #6c757d;
            position: relative;
            z-index: 2;
        }
        .mark-read-btn {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .notification-card:hover .mark-read-btn {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Уведомления</h1>
                <?php if ($unread['unread_count'] > 0): ?>
                    <p class="text-muted mb-0">У вас <?php echo $unread['unread_count']; ?> непрочитанных уведомлений</p>
                <?php endif; ?>
            </div>
            <?php if ($unread['unread_count'] > 0): ?>
                <button class="btn btn-outline-primary" onclick="markAllAsRead()">
                    <i class="bi bi-check2-all me-2"></i>
                    Отметить все как прочитанные
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Нет уведомлений</h5>
                    <p class="text-muted mb-0">У вас пока нет новых уведомлений</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_notifications as $date => $notifications): ?>
                <div class="date-divider">
                    <span><?php echo $date; ?></span>
                </div>
                <?php foreach ($notifications as $notification): ?>
                    <div class="card shadow-sm mb-3 notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?> notification-type-<?php echo $notification['type']; ?>" 
                         id="notification-<?php echo $notification['id']; ?>">
                        <div class="card-body d-flex align-items-start">
                            <div class="notification-icon me-3">
                                <i class="bi <?php echo getNotificationIcon($notification['type']); ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                    <div class="d-flex align-items-center">
                                        <?php if (!$notification['is_read']): ?>
                                            <button class="btn btn-sm btn-link text-muted mark-read-btn p-0 me-3" 
                                                    onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                                <i class="bi bi-check2"></i> Отметить как прочитанное
                                            </button>
                                        <?php endif; ?>
                                        <small class="notification-time">
                                            <?php echo date('H:i', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAsRead(notificationId) {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_read=1&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.getElementById(`notification-${notificationId}`);
                    notification.classList.remove('unread');
                    const markReadBtn = notification.querySelector('.mark-read-btn');
                    if (markReadBtn) markReadBtn.remove();
                    
                    // Обновляем счетчик непрочитанных
                    updateUnreadCount(-1);
                }
            });
        }

        function markAllAsRead() {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_all_read=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-card.unread').forEach(card => {
                        card.classList.remove('unread');
                        const markReadBtn = card.querySelector('.mark-read-btn');
                        if (markReadBtn) markReadBtn.remove();
                    });
                    
                    // Обновляем информацию о непрочитанных
                    const unreadInfo = document.querySelector('.text-muted');
                    if (unreadInfo) unreadInfo.remove();
                    
                    const markAllBtn = document.querySelector('.btn-outline-primary');
                    if (markAllBtn) markAllBtn.remove();
                }
            });
        }

        function updateUnreadCount(change) {
            const unreadInfo = document.querySelector('.text-muted');
            if (unreadInfo) {
                const currentCount = parseInt(unreadInfo.textContent.match(/\d+/)[0]) + change;
                if (currentCount <= 0) {
                    unreadInfo.remove();
                    const markAllBtn = document.querySelector('.btn-outline-primary');
                    if (markAllBtn) markAllBtn.remove();
                } else {
                    unreadInfo.textContent = `У вас ${currentCount} непрочитанных уведомлений`;
                }
            }
        }
    </script>
</body>
</html>

<?php
function getNotificationIcon($type) {
    switch ($type) {
        case 'info':
            return 'bi-info-circle-fill';
        case 'warning':
            return 'bi-exclamation-triangle-fill';
        case 'error':
            return 'bi-x-circle-fill';
        case 'success':
            return 'bi-check-circle-fill';
        default:
            return 'bi-bell-fill';
    }
}
?> 