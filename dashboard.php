<?php
require_once 'config/config.php';
requireLogin();

// Получаем информацию о текущем пользователе
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Базовая информация о пользователе
$sql = "SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        JOIN roles r ON ur.role_id = r.id 
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $conn->error);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Дополнительная информация в зависимости от роли
$roleSpecificData = [];

if ($role_id == ROLE_STUDENT) {
    // Для студента
    $sql = "SELECT s.*, g.name as group_name, d.name as department_name
            FROM students s
            LEFT JOIN student_groups sg ON s.id = sg.student_id
            LEFT JOIN `groups` g ON sg.group_id = g.id
            LEFT JOIN departments d ON g.department_id = d.id
            WHERE s.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Ошибка подготовки запроса: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $roleSpecificData = $result->fetch_assoc();

    // Получаем последние оценки
    $sql = "SELECT ap.*, c.name as course_name, c.code as course_code
            FROM academic_performance ap
            JOIN courses c ON ap.course_id = c.id
            WHERE ap.student_id = ?
            ORDER BY ap.created_at DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $roleSpecificData['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $roleSpecificData['recent_grades'] = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Получаем посещаемость
    $sql = "SELECT a.*, l.topic, c.name as course_name
            FROM attendance a
            JOIN lessons l ON a.lesson_id = l.id
            JOIN courses c ON l.course_id = c.id
            WHERE a.student_id = ?
            ORDER BY a.date DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $roleSpecificData['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $roleSpecificData['recent_attendance'] = $result->fetch_all(MYSQLI_ASSOC);
    }

} elseif ($role_id == ROLE_TEACHER) {
    // Для преподавателя
    $sql = "SELECT t.*, d.name as department_name
            FROM teachers t
            LEFT JOIN departments d ON t.department_id = d.id
            WHERE t.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Ошибка подготовки запроса: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $roleSpecificData = $result->fetch_assoc();

    // Получаем курсы преподавателя
    $sql = "SELECT DISTINCT
                c.id as course_id,
                c.name as course_name,
                c.code as course_code,
                g.name as group_name,
                g.id as group_id,
                (
                    SELECT COUNT(DISTINCT sg2.student_id) 
                    FROM student_groups sg2 
                    WHERE sg2.group_id = g.id AND sg2.status = 'active'
                ) as students_count
            FROM lessons l
            JOIN courses c ON l.course_id = c.id
            JOIN `groups` g ON l.group_id = g.id
            WHERE l.teacher_id = ?
            AND c.status = 'active'
            ORDER BY c.name, g.name
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $roleSpecificData['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $roleSpecificData['courses'] = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Получаем общую статистику
    $sql = "SELECT 
                COUNT(DISTINCT l.course_id) as total_courses,
                COUNT(DISTINCT l.group_id) as total_groups,
                COUNT(DISTINCT sg.student_id) as total_students,
                COUNT(DISTINCT l.id) as total_lessons
            FROM lessons l
            JOIN `groups` g ON l.group_id = g.id
            LEFT JOIN student_groups sg ON g.id = sg.group_id AND sg.status = 'active'
            WHERE l.teacher_id = ?
            AND EXISTS (
                SELECT 1 
                FROM courses c 
                WHERE c.id = l.course_id 
                AND c.status = 'active'
            )";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $roleSpecificData['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $roleSpecificData['stats'] = $result->fetch_assoc();
    }

} elseif ($role_id == ROLE_ADMIN) {
    // Для администратора - статистика системы
    $sql = "SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'active') as active_students,
            (SELECT COUNT(*) FROM teachers) as total_teachers,
            (SELECT COUNT(*) FROM courses WHERE status = 'active') as active_courses,
            (SELECT COUNT(*) FROM departments) as total_departments";
    
    $result = $conn->query($sql);
    if ($result) {
        $roleSpecificData = $result->fetch_assoc();
    }

    // Последние действия в системе
    $sql = "SELECT al.*, u.first_name, u.last_name
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    if ($result) {
        $roleSpecificData['recent_activities'] = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row">
            <!-- Левая колонка - информация о пользователе -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['last_name'] . ' ' . $user['first_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                    </div>
            </div>
            
                <?php if ($role_id == ROLE_STUDENT && isset($roleSpecificData['group_name'])): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Учебная информация</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-people me-2"></i>
                                Группа: <?php echo htmlspecialchars($roleSpecificData['group_name']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-building me-2"></i>
                                Кафедра: <?php echo htmlspecialchars($roleSpecificData['department_name'] ?? 'Не назначена'); ?>
                            </li>
                            <li>
                                <i class="bi bi-mortarboard me-2"></i>
                                Семестр: <?php echo $roleSpecificData['current_semester']; ?>
                            </li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($role_id == ROLE_TEACHER && isset($roleSpecificData['department_name'])): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Информация о преподавателе</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-building me-2"></i>
                                Кафедра: <?php echo htmlspecialchars($roleSpecificData['department_name']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-award me-2"></i>
                                Должность: <?php echo htmlspecialchars($roleSpecificData['position']); ?>
                            </li>
                            <?php if ($roleSpecificData['academic_degree']): ?>
                            <li>
                                <i class="bi bi-book me-2"></i>
                                Степень: <?php echo htmlspecialchars($roleSpecificData['academic_degree']); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Правая колонка - основной контент -->
            <div class="col-md-8">
                <?php if ($role_id == ROLE_STUDENT): ?>
                    <!-- Блок для студента -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Последние оценки</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($roleSpecificData['recent_grades'])): ?>
                                <p class="text-muted mb-0">Нет данных об оценках</p>
                    <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Дисциплина</th>
                                                <th>Оценка</th>
                                                <th>Дата</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleSpecificData['recent_grades'] as $grade): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($grade['course_name']); ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($grade['course_code']); ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getGradeClass($grade['final_grade']); ?>">
                                                            <?php echo number_format($grade['final_grade'], 1); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d.m.Y', strtotime($grade['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                    <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Посещаемость</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($roleSpecificData['recent_attendance'])): ?>
                                <p class="text-muted mb-0">Нет данных о посещаемости</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Дата</th>
                                                <th>Дисциплина</th>
                                                <th>Тема</th>
                                                <th>Статус</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleSpecificData['recent_attendance'] as $attendance): ?>
                                                <tr>
                                                    <td><?php echo date('d.m.Y', strtotime($attendance['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($attendance['course_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($attendance['topic']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getAttendanceClass($attendance['status']); ?>">
                                                            <?php echo getAttendanceStatus($attendance['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                        <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($role_id == ROLE_TEACHER): ?>
                    <!-- Статистика преподавателя -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo $roleSpecificData['stats']['total_courses']; ?></h3>
                                    <p class="card-text text-muted">Курсов</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo $roleSpecificData['stats']['total_groups']; ?></h3>
                                    <p class="card-text text-muted">Групп</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo $roleSpecificData['stats']['total_students']; ?></h3>
                                    <p class="card-text text-muted">Студентов</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo $roleSpecificData['stats']['total_lessons']; ?></h3>
                                    <p class="card-text text-muted">Занятий</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Последние курсы -->
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">Мои курсы</h6>
                            <a href="teacher_courses.php" class="btn btn-sm btn-primary">
                                Все курсы
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($roleSpecificData['courses'])): ?>
                                <div class="text-muted">У вас пока нет активных курсов</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Дисциплина</th>
                                                <th>Группа</th>
                                                <th>Студенты</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleSpecificData['courses'] as $course): ?>
                                                <tr>
                                                    <td>
                                                        <a href="course.php?id=<?php echo $course['course_id']; ?>" 
                                                           class="text-decoration-none">
                                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                                        </a>
                                                        <div class="small text-muted">
                                                            <?php echo htmlspecialchars($course['course_code']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($course['group_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?php echo $course['students_count']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="teacher_students.php?course_id=<?php echo $course['course_id']; ?>&group_id=<?php echo $course['group_id']; ?>" 
                                                               class="btn btn-outline-primary" 
                                                               title="Студенты">
                                                                <i class="bi bi-people"></i>
                                                            </a>
                                                            <a href="teacher_attendance.php?course_id=<?php echo $course['course_id']; ?>&group_id=<?php echo $course['group_id']; ?>" 
                                                               class="btn btn-outline-success" 
                                                               title="Посещаемость">
                                                                <i class="bi bi-calendar-check"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($role_id == ROLE_ADMIN): ?>
                    <!-- Блок для администратора -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Статистика системы</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Студенты</h6>
                                            <h3 class="mb-0"><?php echo $roleSpecificData['active_students']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Преподаватели</h6>
                                            <h3 class="mb-0"><?php echo $roleSpecificData['total_teachers']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Курсы</h6>
                                            <h3 class="mb-0"><?php echo $roleSpecificData['active_courses']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-warning">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Кафедры</h6>
                                            <h3 class="mb-0"><?php echo $roleSpecificData['total_departments']; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Последние действия</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($roleSpecificData['recent_activities'])): ?>
                                <p class="text-muted mb-0">Нет данных о действиях</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Время</th>
                                                <th>Пользователь</th>
                                                <th>Действие</th>
                                                <th>Категория</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleSpecificData['recent_activities'] as $activity): ?>
                                                <tr>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($activity['first_name']) {
                                                            echo htmlspecialchars($activity['last_name'] . ' ' . $activity['first_name']);
                                                        } else {
                                                            echo 'Система';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getActivityClass($activity['category']); ?>">
                                                            <?php echo getActivityCategory($activity['category']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getGradeClass($grade) {
    if ($grade >= 90) return 'success';
    if ($grade >= 70) return 'info';
    if ($grade >= 50) return 'warning';
    return 'danger';
}

function getAttendanceClass($status) {
    $classes = [
        'present' => 'success',
        'absent' => 'danger',
        'late' => 'warning'
    ];
    return $classes[$status] ?? 'secondary';
}

function getAttendanceStatus($status) {
    $statuses = [
        'present' => 'Присутствовал',
        'absent' => 'Отсутствовал',
        'late' => 'Опоздал'
    ];
    return $statuses[$status] ?? 'Неизвестно';
}

function getActivityClass($category) {
    $classes = [
        'academic' => 'info',
        'administrative' => 'primary',
        'system' => 'secondary'
    ];
    return $classes[$category] ?? 'secondary';
}

function getActivityCategory($category) {
    $categories = [
        'academic' => 'Учебная',
        'administrative' => 'Административная',
        'system' => 'Системная'
    ];
    return $categories[$category] ?? 'Неизвестно';
}
?>