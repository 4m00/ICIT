<?php
// Получаем информацию о текущем пользователе
$currentUserId = $_SESSION['user_id'] ?? null;

if ($currentUserId) {
    try {
        $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $currentUserId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $currentUser = [
            'first_name' => $_SESSION['first_name'] ?? 'Пользователь',
            'last_name' => $_SESSION['last_name'] ?? ''
        ];
    }
}

// Получаем количество непрочитанных уведомлений
$sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$unread_notifications = $stmt->get_result()->fetch_assoc()['count'];

// Получаем последние уведомления
$sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-mortarboard-fill text-primary me-2"></i>
            <?php echo APP_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($_SESSION['role_id'] == ROLE_ADMIN): ?>
                    <!-- Меню администратора -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-house-door me-1"></i>
                            Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                           href="users.php">
                            <i class="bi bi-people me-1"></i>
                            Пользователи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>" 
                           href="departments.php">
                            <i class="bi bi-building me-1"></i>
                            Кафедры
                        </a>
                    </li>

                <?php elseif ($_SESSION['role_id'] == ROLE_DEKANAT): ?>
                    <!-- Меню сотрудника деканата -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-house-door me-1"></i>
                            Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" 
                           href="students.php">
                            <i class="bi bi-people me-1"></i>
                            Студенты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>" 
                           href="schedule.php">
                            <i class="bi bi-calendar3 me-1"></i>
                            Расписание
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" 
                           href="reports.php">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            Отчеты
                        </a>
                    </li>

                <?php elseif ($_SESSION['role_id'] == ROLE_TEACHER): ?>
                    <!-- Меню преподавателя -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-house-door me-1"></i>
                            Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_courses.php' ? 'active' : ''; ?>" 
                           href="teacher_courses.php">
                            <i class="bi bi-mortarboard me-1"></i>
                            Преподавание
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_schedule.php' ? 'active' : ''; ?>" 
                           href="teacher_schedule.php">
                            <i class="bi bi-calendar3 me-1"></i>
                            Расписание
                        </a>
                    </li>

                <?php elseif ($_SESSION['role_id'] == ROLE_STUDENT): ?>
                    <!-- Меню студента -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-house-door me-1"></i>
                            Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>" 
                           href="schedule.php">
                            <i class="bi bi-calendar3 me-1"></i>
                            Расписание
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'grades.php' ? 'active' : ''; ?>" 
                           href="grades.php">
                            <i class="bi bi-mortarboard me-1"></i>
                            Оценки
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>" 
                           href="attendance.php">
                            <i class="bi bi-person-check me-1"></i>
                            Посещаемость
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <!-- Уведомления -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" 
                       role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_notifications; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 300px;">
                        <h6 class="dropdown-header">Уведомления</h6>
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <a class="dropdown-item d-flex align-items-center py-2" href="#">
                                    <div class="me-3">
                                        <div class="bg-primary text-white rounded-circle p-2">
                                            <i class="bi bi-bell-fill"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">
                                            <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                        </div>
                                        <span class="font-weight-bold"><?php echo htmlspecialchars($notification['message']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center small text-gray-500" href="notifications.php">
                                Показать все уведомления
                            </a>
                        <?php else: ?>
                            <div class="dropdown-item text-center">Нет новых уведомлений</div>
                        <?php endif; ?>
                    </div>
                </li>

                <!-- Профиль -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($currentUser['last_name'] . ' ' . $currentUser['first_name']); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header">
                            <div class="fw-bold"><?php echo htmlspecialchars($currentUser['last_name'] . ' ' . $currentUser['first_name']); ?></div>
                            <div class="small text-muted">
                                <?php 
                                $role_name = isset($_SESSION['role_name']) ? $_SESSION['role_name'] : 'Пользователь';
                                echo ucfirst($role_name); 
                                ?>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="profile.php">
                            <i class="bi bi-person me-2"></i>Профиль
                        </a>
                        <a class="dropdown-item" href="settings.php">
                            <i class="bi bi-gear me-2"></i>Настройки
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Выход
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Отступ для фиксированной навигационной панели -->
<div style="margin-top: 70px;"></div> 