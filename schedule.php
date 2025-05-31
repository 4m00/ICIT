<?php
require_once 'config/config.php';
requireLogin();

// Проверяем, что пользователь - студент
if ($_SESSION['role_id'] != ROLE_STUDENT) {
    header('Location: dashboard.php');
    exit;
}

// Получаем ID студента
$user_id = $_SESSION['user_id'];

// Отладочная информация
error_reporting(E_ALL);
ini_set('display_errors', 1);

$sql = "SELECT s.id as student_id, sg.group_id, g.name as group_name 
        FROM students s
        LEFT JOIN student_groups sg ON s.id = sg.student_id
        LEFT JOIN `groups` g ON sg.group_id = g.id
        WHERE s.user_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $conn->error);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die('Ошибка: студент не найден');
}

// Получаем текущее расписание для группы студента
$sql = "SELECT 
            s.*, 
            c.name as course_name,
            c.code as course_code,
            CONCAT(u.last_name, ' ', u.first_name) as teacher_name,
            r.name as room_name
        FROM schedule s
        JOIN courses c ON s.course_id = c.id
        JOIN teachers t ON s.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        LEFT JOIN rooms r ON s.room = r.id
        WHERE s.group_id = ? AND s.is_active = 1
        ORDER BY s.day_of_week, s.start_time";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса расписания: ' . $conn->error);
}

$stmt->bind_param('i', $student['group_id']);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_all(MYSQLI_ASSOC);

// Группируем расписание по дням недели
$days = [
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота'
];

$scheduleByDay = [];
foreach ($schedule as $lesson) {
    $scheduleByDay[$lesson['day_of_week']][] = $lesson;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .day-card {
            transition: transform 0.2s;
        }
        .day-card:hover {
            transform: translateY(-5px);
        }
        .schedule-header {
            background: rgba(var(--bs-primary-rgb), 0.1);
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">Расписание занятий</h1>
                <p class="text-muted mb-0">Группа <?php echo htmlspecialchars($student['group_name']); ?></p>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Печать
                </button>
                <button class="btn btn-outline-primary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </button>
            </div>
        </div>

        <div class="row">
            <?php foreach ($days as $dayNum => $dayName): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm day-card">
                        <div class="card-header schedule-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar-day me-2"></i>
                                <?php echo $dayName; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($scheduleByDay[$dayNum])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 20%">Время</th>
                                                <th style="width: 35%">Дисциплина</th>
                                                <th style="width: 25%">Преподаватель</th>
                                                <th style="width: 20%">Тип</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($scheduleByDay[$dayNum] as $lesson): ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <strong>
                                                            <?php 
                                                            echo substr($lesson['start_time'], 0, 5) . ' - ' . 
                                                                 substr($lesson['end_time'], 0, 5); 
                                                            ?>
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($lesson['course_name']); ?>
                                                        <div class="small text-muted">
                                                            <?php echo htmlspecialchars($lesson['course_code']); ?>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <?php echo htmlspecialchars($lesson['teacher_name']); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge bg-<?php echo getLessonTypeClass($lesson['type']); ?>">
                                                            <?php echo getLessonType($lesson['type']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0 text-center py-3">
                                    <i class="bi bi-calendar-x me-2"></i>
                                    Нет занятий
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToExcel() {
            // Здесь будет реализована функция экспорта в Excel
            alert('Функция экспорта в Excel будет доступна в следующем обновлении');
        }
    </script>
</body>
</html>

<?php
function getLessonTypeClass($type) {
    $classes = [
        'lecture' => 'primary',
        'practice' => 'success',
        'lab' => 'info',
        'exam' => 'danger'
    ];
    return $classes[$type] ?? 'secondary';
}

function getLessonType($type) {
    $types = [
        'lecture' => 'Лекция',
        'practice' => 'Практика',
        'lab' => 'Лабораторная',
        'exam' => 'Экзамен'
    ];
    return $types[$type] ?? 'Неизвестно';
}
?> 