<?php
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Проверка авторизации
if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Используем существующее подключение к базе данных из config/database.php
global $conn;

// Получаем информацию о студенте
$student_info_query = "
    SELECT 
        s.id as student_id,
        s.student_id_number,
        s.current_semester,
        s.faculty_id,
        f.name as faculty_name,
        g.id as group_id,
        g.name as group_name,
        d.id as department_id,
        d.name as department_name,
        CONCAT(u.last_name, ' ', u.first_name) as student_name
    FROM students s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN faculties f ON s.faculty_id = f.id
    LEFT JOIN student_groups sg ON s.id = sg.student_id
    LEFT JOIN `groups` g ON sg.group_id = g.id
    LEFT JOIN departments d ON g.department_id = d.id
    WHERE s.user_id = ?";

$stmt = $conn->prepare($student_info_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student_info = $result->fetch_assoc();

// Общая статистика посещаемости
$stats_query = "
    SELECT 
        COUNT(*) as total_lessons,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        ROUND((SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as attendance_rate
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE s.user_id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Посещаемость по предметам
$courses_query = "
    SELECT 
        c.name as course_name,
        c.code as course_code,
        COUNT(*) as total_lessons,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        ROUND((SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as course_attendance_rate
    FROM attendance a
    JOIN lessons l ON a.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    JOIN students s ON a.student_id = s.id
    WHERE s.user_id = ?
    GROUP BY c.id, c.name, c.code
    ORDER BY c.name";

$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$courses_attendance = $result->fetch_all(MYSQLI_ASSOC);

// Детальная история посещений
$history_query = "
    SELECT 
        a.date,
        a.status,
        l.type as lesson_type,
        c.name as course_name,
        c.code as course_code,
        l.start_time,
        l.end_time,
        l.room,
        l.topic,
        CONCAT(u.last_name, ' ', u.first_name) as teacher_name
    FROM students s
    JOIN student_groups sg ON s.id = sg.student_id
    JOIN lessons l ON l.group_id = sg.group_id
    LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = s.id
    JOIN courses c ON l.course_id = c.id
    JOIN teachers t ON l.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE s.user_id = ?
    ORDER BY l.date DESC, l.start_time DESC
    LIMIT 30";

$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$attendance_history = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Посещаемость - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .attendance-card {
            transition: transform 0.2s;
        }
        .attendance-card:hover {
            transform: translateY(-5px);
        }
        .status-present {
            color: #198754;
        }
        .status-late {
            color: #ffc107;
        }
        .status-absent {
            color: #dc3545;
        }
        .progress {
            height: 10px;
        }
        .history-table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row">
            <!-- Информация о студенте -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Информация о студенте</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-person me-2"></i>
                                <?php echo htmlspecialchars($student_info['student_name']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-person me-2"></i>
                                Номер студента: <?php echo htmlspecialchars($student_info['student_id_number']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-people me-2"></i>
                                Группа: <?php echo htmlspecialchars($student_info['group_name']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-building me-2"></i>
                                Кафедра: <?php echo htmlspecialchars($student_info['department_name']); ?>
                            </li>
                            <li>
                                <i class="bi bi-mortarboard me-2"></i>
                                Текущий семестр: <?php echo $student_info['current_semester']; ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Общая статистика -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Общая статистика</h5>
                        <div class="text-center mb-3">
                            <h2 class="display-4 mb-0">
                                <span class="badge bg-<?php echo getAttendanceClass($stats['attendance_rate']); ?>">
                                    <?php echo $stats['attendance_rate']; ?>%
                                </span>
                            </h2>
                            <p class="text-muted">Общая посещаемость</p>
                        </div>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-check-circle status-present me-2"></i>
                                Присутствия: <?php echo $stats['present_count']; ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-clock status-late me-2"></i>
                                Опоздания: <?php echo $stats['late_count']; ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-x-circle status-absent me-2"></i>
                                Пропуски: <?php echo $stats['absent_count']; ?>
                            </li>
                            <li>
                                <i class="bi bi-calendar3 me-2"></i>
                                Всего занятий: <?php echo $stats['total_lessons']; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Посещаемость по предметам и история -->
            <div class="col-md-8">
                <!-- Посещаемость по предметам -->
                <?php foreach ($courses_attendance as $course): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($course['course_code']); ?>)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-<?php echo getAttendanceClass($course['course_attendance_rate']); ?>">
                                    Посещаемость: <?php echo $course['course_attendance_rate']; ?>%
                                </span>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo ($course['present_count'] / $course['total_lessons']) * 100; ?>%">
                                </div>
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?php echo ($course['late_count'] / $course['total_lessons']) * 100; ?>%">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Присутствия: <?php echo $course['present_count']; ?></span>
                                <span>Опоздания: <?php echo $course['late_count']; ?></span>
                                <span>Пропуски: <?php echo $course['absent_count']; ?></span>
                                <span>Всего занятий: <?php echo $course['total_lessons']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- История посещений -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">История посещений</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover history-table">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Предмет</th>
                                        <th>Тип занятия</th>
                                        <th>Время</th>
                                        <th>Статус</th>
                                        <th>Преподаватель</th>
                                        <th>Аудитория</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_history as $record): ?>
                                        <tr class="<?php echo getStatusClass($record['status']); ?>">
                                            <td><?php echo date('d.m.Y', strtotime($record['date'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($record['course_name']); ?>
                                                <small class="text-muted">(<?php echo htmlspecialchars($record['course_code']); ?>)</small>
                                            </td>
                                            <td>
                                                <?php 
                                                    $types = [
                                                        'lecture' => '<i class="bi bi-book"></i> Лекция',
                                                        'practice' => '<i class="bi bi-pencil"></i> Практика',
                                                        'lab' => '<i class="bi bi-pc-display"></i> Лабораторная',
                                                        'exam' => '<i class="bi bi-journal-check"></i> Экзамен'
                                                    ];
                                                    echo $types[$record['lesson_type']] ?? $record['lesson_type'];
                                                ?>
                                            </td>
                                            <td><?php echo date('H:i', strtotime($record['start_time'])) . ' - ' . 
                                                       date('H:i', strtotime($record['end_time'])); ?></td>
                                            <td>
                                                <?php 
                                                    $status_icons = [
                                                        'present' => '<span class="text-success"><i class="bi bi-check-circle"></i> Присутствие</span>',
                                                        'late' => '<span class="text-warning"><i class="bi bi-clock"></i> Опоздание</span>',
                                                        'absent' => '<span class="text-danger"><i class="bi bi-x-circle"></i> Отсутствие</span>'
                                                    ];
                                                    echo $status_icons[$record['status']] ?? $record['status'];
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                                            <td>
                                                <i class="bi bi-building"></i>
                                                <?php echo htmlspecialchars($record['room']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getAttendanceClass($rate) {
    if ($rate >= 90) return 'success';
    if ($rate >= 70) return 'info';
    if ($rate >= 50) return 'warning';
    return 'danger';
}

function getStatusClass($status) {
    switch ($status) {
        case 'present':
            return 'table-default';
        case 'late':
            return 'table-warning bg-opacity-25';
        case 'absent':
            return 'table-danger bg-opacity-25';
        default:
            return '';
    }
}
?> 