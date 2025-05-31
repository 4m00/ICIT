<?php
require_once 'config/config.php';
require_once 'config/database.php';
requireLogin();

// Проверяем, что пользователь - преподаватель
if ($_SESSION['role_id'] != ROLE_TEACHER) {
    header('Location: dashboard.php');
    exit;
}

// Получаем ID преподавателя
$user_id = $_SESSION['user_id'];

// Получаем информацию о преподавателе
$sql = "SELECT 
            t.id as teacher_id,
            t.position,
            t.academic_degree,
            t.department_id,
            d.name as department_name,
            CONCAT(u.last_name, ' ', u.first_name) as teacher_name
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN departments d ON t.department_id = d.id
        WHERE t.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    die('Ошибка: преподаватель не найден');
}

// Получаем текущую дату и неделю
$current_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($current_date)));

// Получаем расписание на неделю
$sql = "SELECT 
            l.id,
            l.date,
            l.start_time,
            l.end_time,
            l.type,
            l.topic,
            l.room,
            c.name as course_name,
            c.code as course_code,
            g.name as group_name,
            g.year_of_study,
            (
                SELECT COUNT(DISTINCT sg.student_id)
                FROM student_groups sg
                WHERE sg.group_id = g.id AND sg.status = 'active'
            ) as students_count
        FROM lessons l
        JOIN courses c ON l.course_id = c.id
        JOIN `groups` g ON l.group_id = g.id
        WHERE l.teacher_id = ?
        AND l.date BETWEEN ? AND ?
        ORDER BY l.date, l.start_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $teacher['teacher_id'], $week_start, $week_end);
$stmt->execute();
$result = $stmt->get_result();
$lessons = $result->fetch_all(MYSQLI_ASSOC);

// Группируем занятия по дням
$schedule = [];
$weekdays = [
    'Понедельник' => [],
    'Вторник' => [],
    'Среда' => [],
    'Четверг' => [],
    'Пятница' => [],
    'Суббота' => [],
    'Воскресенье' => []
];

foreach ($lessons as $lesson) {
    $day_name = strftime('%A', strtotime($lesson['date']));
    $day_name = mb_convert_case($day_name, MB_CASE_TITLE, "UTF-8");
    $weekdays[$day_name][] = $lesson;
}

// Получаем предыдущую и следующую недели
$prev_week = date('Y-m-d', strtotime('-1 week', strtotime($current_date)));
$next_week = date('Y-m-d', strtotime('+1 week', strtotime($current_date)));
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
        .lesson-card {
            transition: all 0.2s;
        }
        .lesson-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
        .type-lecture { border-left: 4px solid var(--bs-primary); }
        .type-practice { border-left: 4px solid var(--bs-success); }
        .type-lab { border-left: 4px solid var(--bs-info); }
        .type-exam { border-left: 4px solid var(--bs-danger); }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row">
            <!-- Информация о преподавателе -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Информация о преподавателе</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-person me-2"></i>
                                <?php echo htmlspecialchars($teacher['teacher_name'] ?? 'Не указано'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-award me-2"></i>
                                <?php echo htmlspecialchars($teacher['position'] ?? 'Не указано'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-mortarboard me-2"></i>
                                <?php echo htmlspecialchars($teacher['academic_degree'] ?? 'Не указано'); ?>
                            </li>
                            <li>
                                <i class="bi bi-building me-2"></i>
                                <?php echo htmlspecialchars($teacher['department_name'] ?? 'Не указано'); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Расписание -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                Расписание на неделю
                                <small class="text-muted">
                                    (<?php echo date('d.m.Y', strtotime($week_start)); ?> - 
                                    <?php echo date('d.m.Y', strtotime($week_end)); ?>)
                                </small>
                            </h5>
                            <div class="btn-group">
                                <a href="?date=<?php echo $prev_week; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                                <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary">
                                    Сегодня
                                </a>
                                <a href="?date=<?php echo $next_week; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lessons)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                На этой неделе занятий нет
                            </div>
                        <?php else: ?>
                            <?php foreach ($weekdays as $day_name => $day_lessons): ?>
                                <h6 class="mt-4 mb-3"><?php echo $day_name; ?></h6>
                                <?php if (empty($day_lessons)): ?>
                                    <div class="text-muted small">Нет занятий</div>
                                <?php else: ?>
                                    <?php foreach ($day_lessons as $lesson): ?>
                                        <div class="card mb-2 lesson-card type-<?php echo $lesson['type']; ?>">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-2">
                                                        <div class="fw-bold">
                                                            <?php echo date('H:i', strtotime($lesson['start_time'])); ?> - 
                                                            <?php echo date('H:i', strtotime($lesson['end_time'])); ?>
                                                        </div>
                                                        <div class="small text-muted">
                                                            Ауд. <?php echo htmlspecialchars($lesson['room']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="fw-bold">
                                                            <?php echo htmlspecialchars($lesson['course_name']); ?>
                                                        </div>
                                                        <div class="small text-muted">
                                                            <?php echo htmlspecialchars($lesson['course_code']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div>
                                                            <?php echo htmlspecialchars($lesson['group_name']); ?>
                                                            <span class="badge bg-primary ms-1">
                                                                <?php echo $lesson['students_count']; ?>
                                                            </span>
                                                        </div>
                                                        <div class="small text-muted">
                                                            <?php echo getLessonType($lesson['type']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="teacher_attendance.php?course_id=<?php echo $lesson['course_id']; ?>&group_id=<?php echo $lesson['group_id']; ?>&date=<?php echo $lesson['date']; ?>" 
                                                               class="btn btn-outline-success" 
                                                               title="Посещаемость">
                                                                <i class="bi bi-calendar-check"></i>
                                                            </a>
                                                            <a href="teacher_students.php?course_id=<?php echo $lesson['course_id']; ?>&group_id=<?php echo $lesson['group_id']; ?>" 
                                                               class="btn btn-outline-primary" 
                                                               title="Студенты">
                                                                <i class="bi bi-people"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php if ($lesson['topic']): ?>
                                                    <div class="small text-muted mt-1">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        <?php echo htmlspecialchars($lesson['topic']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
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