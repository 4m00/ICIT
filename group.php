<?php
require_once 'config/config.php';
require_once 'config/database.php';
requireLogin();

// Получаем ID группы из URL
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$group_id) {
    header('Location: dashboard.php');
    exit;
}

// Получаем информацию о группе
$sql = "SELECT 
            g.*,
            d.name as department_name,
            (
                SELECT COUNT(DISTINCT sg.student_id)
                FROM student_groups sg
                WHERE sg.group_id = g.id AND sg.status = 'active'
            ) as students_count
        FROM `groups` g
        LEFT JOIN departments d ON g.department_id = d.id
        WHERE g.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка при подготовке запроса: ' . $conn->error);
}

$stmt->bind_param('i', $group_id);
if (!$stmt->execute()) {
    die('Ошибка при выполнении запроса: ' . $stmt->error);
}

$result = $stmt->get_result();
$group = $result->fetch_assoc();

if (!$group) {
    die('Группа не найдена');
}

// Получаем список студентов группы
$sql = "SELECT 
            s.id,
            s.student_id_number,
            CONCAT(u.last_name, ' ', u.first_name) as student_name,
            sg.status,
            sg.joined_at
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN student_groups sg ON s.id = sg.student_id
        WHERE sg.group_id = ?
        ORDER BY u.last_name, u.first_name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка при подготовке запроса студентов: ' . $conn->error);
}

$stmt->bind_param('i', $group_id);
if (!$stmt->execute()) {
    die('Ошибка при выполнении запроса студентов: ' . $stmt->error);
}

$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Получаем курсы группы
$sql = "SELECT DISTINCT
            c.*,
            CONCAT(u.last_name, ' ', u.first_name) as teacher_name,
            t.position as teacher_position,
            t.academic_degree as teacher_degree,
            (
                SELECT COUNT(*)
                FROM lessons l2
                WHERE l2.course_id = c.id AND l2.group_id = ?
            ) as lessons_count
        FROM courses c
        JOIN lessons l ON c.id = l.course_id
        JOIN teachers t ON l.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE l.group_id = ?
        AND c.status = 'active'
        ORDER BY c.name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка при подготовке запроса курсов: ' . $conn->error);
}

$stmt->bind_param('ii', $group_id, $group_id);
if (!$stmt->execute()) {
    die('Ошибка при выполнении запроса курсов: ' . $stmt->error);
}

$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);

// Получаем расписание на неделю
$current_date = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

$sql = "SELECT 
            l.*,
            c.name as course_name,
            c.code as course_code,
            CONCAT(u.last_name, ' ', u.first_name) as teacher_name
        FROM lessons l
        JOIN courses c ON l.course_id = c.id
        JOIN teachers t ON l.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE l.group_id = ?
        AND l.date BETWEEN ? AND ?
        ORDER BY l.date, l.start_time";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка при подготовке запроса расписания: ' . $conn->error);
}

$stmt->bind_param('iss', $group_id, $week_start, $week_end);
if (!$stmt->execute()) {
    die('Ошибка при выполнении запроса расписания: ' . $stmt->error);
}

$result = $stmt->get_result();
$schedule = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Группа <?php echo htmlspecialchars($group['name']); ?> - <?php echo APP_NAME; ?></title>
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
            <!-- Информация о группе -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($group['name']); ?>
                            <span class="badge bg-primary">
                                <?php echo $group['students_count']; ?> студентов
                            </span>
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-mortarboard me-2"></i>
                                <?php echo $group['year_of_study']; ?> курс
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-building me-2"></i>
                                <?php echo htmlspecialchars($group['department_name'] ?? 'Не указано'); ?>
                            </li>
                            <?php if ($group['description']): ?>
                                <li class="mt-3">
                                    <div class="text-muted">
                                        <?php echo nl2br(htmlspecialchars($group['description'])); ?>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Список студентов -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Студенты группы</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="text-muted">В группе нет студентов</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($students as $student): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div><?php echo htmlspecialchars($student['student_name']); ?></div>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($student['student_id_number']); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $student['status'] === 'active' ? 'Активный' : 'Неактивный'; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Курсы -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Курсы</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($courses)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Нет активных курсов
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($courses as $course): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <a href="course.php?id=<?php echo $course['id']; ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($course['name']); ?>
                                                    </a>
                                                </h6>
                                                <div class="small text-muted mb-2">
                                                    <?php echo htmlspecialchars($course['code']); ?>
                                                </div>
                                                <ul class="list-unstyled mb-0 small">
                                                    <li class="mb-1">
                                                        <i class="bi bi-person me-2"></i>
                                                        <?php echo htmlspecialchars($course['teacher_name']); ?>
                                                    </li>
                                                    <li class="mb-1">
                                                        <i class="bi bi-calendar3 me-2"></i>
                                                        <?php echo $course['lessons_count']; ?> занятий
                                                    </li>
                                                    <li>
                                                        <i class="bi bi-clock me-2"></i>
                                                        <?php echo $course['credits'] * 36; ?> ак. часов
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Расписание на неделю -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Расписание на неделю
                            <small class="text-muted">
                                (<?php echo date('d.m.Y', strtotime($week_start)); ?> - 
                                <?php echo date('d.m.Y', strtotime($week_end)); ?>)
                            </small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedule)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                На этой неделе занятий нет
                            </div>
                        <?php else: ?>
                            <?php 
                            $current_date = null;
                            foreach ($schedule as $lesson): 
                                if ($current_date !== $lesson['date']):
                                    if ($current_date !== null) echo '</div>';
                                    $current_date = $lesson['date'];
                                    $day_name = strftime('%A', strtotime($lesson['date']));
                                    $day_name = mb_convert_case($day_name, MB_CASE_TITLE, "UTF-8");
                            ?>
                                <h6 class="mt-3 mb-2">
                                    <?php echo $day_name; ?>, 
                                    <?php echo date('d.m.Y', strtotime($lesson['date'])); ?>
                                </h6>
                                <div class="lessons">
                            <?php endif; ?>
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
                                                <div class="col-md-4">
                                                    <div>
                                                        <?php echo htmlspecialchars($lesson['teacher_name']); ?>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <?php echo getLessonType($lesson['type']); ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <?php if ($_SESSION['role_id'] == ROLE_TEACHER): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="teacher_attendance.php?course_id=<?php echo $lesson['course_id']; ?>&group_id=<?php echo $group_id; ?>&date=<?php echo $lesson['date']; ?>" 
                                                               class="btn btn-outline-success" 
                                                               title="Посещаемость">
                                                                <i class="bi bi-calendar-check"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
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
                            <?php if (!empty($schedule)) echo '</div>'; ?>
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