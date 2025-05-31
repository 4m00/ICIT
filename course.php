<?php
require_once 'config/config.php';
require_once 'config/database.php';
requireLogin();

// Получаем ID курса из URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    header('Location: dashboard.php');
    exit;
}

// Получаем информацию о курсе
$sql = "SELECT 
            c.*,
            d.name as department_name,
            CONCAT(u.last_name, ' ', u.first_name) as teacher_name,
            t.position as teacher_position,
            t.academic_degree as teacher_degree
        FROM courses c
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN (
            SELECT DISTINCT course_id, teacher_id
            FROM lessons
            WHERE course_id = ?
            LIMIT 1
        ) l ON c.id = l.course_id
        LEFT JOIN teachers t ON l.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE c.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка при подготовке запроса: ' . $conn->error);
}

$stmt->bind_param('ii', $course_id, $course_id);
if (!$stmt->execute()) {
    die('Ошибка при выполнении запроса: ' . $stmt->error);
}

$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    die('Курс не найден');
}

// Получаем группы, изучающие курс
$sql = "SELECT DISTINCT
            g.*,
            (
                SELECT COUNT(DISTINCT sg.student_id)
                FROM student_groups sg
                WHERE sg.group_id = g.id AND sg.status = 'active'
            ) as students_count
        FROM `groups` g
        JOIN lessons l ON g.id = l.group_id
        WHERE l.course_id = ?
        ORDER BY g.name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка при подготовке запроса групп: ' . $conn->error);
}

$stmt->bind_param('i', $course_id);
if (!$stmt->execute()) {
    die('Ошибка при выполнении запроса групп: ' . $stmt->error);
}

$result = $stmt->get_result();
$groups = $result->fetch_all(MYSQLI_ASSOC);

// Получаем расписание занятий
$sql = "SELECT 
            l.*,
            g.name as group_name,
            g.id as group_id,
            CONCAT(u.last_name, ' ', u.first_name) as teacher_name
        FROM lessons l
        JOIN `groups` g ON l.group_id = g.id
        JOIN teachers t ON l.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE l.course_id = ?
        AND l.date >= CURDATE()
        ORDER BY l.date, l.start_time
        LIMIT 10";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка при подготовке запроса расписания: ' . $conn->error);
}

$stmt->bind_param('i', $course_id);
if (!$stmt->execute()) {
    die('Ошибка при выполнении запроса расписания: ' . $stmt->error);
}

$result = $stmt->get_result();
$upcoming_lessons = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['name']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row">
            <!-- Информация о курсе -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($course['name']); ?>
                            <span class="badge bg-<?php echo $course['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo $course['status'] === 'active' ? 'Активный' : 'Неактивный'; ?>
                            </span>
                        </h5>
                        <div class="text-muted mb-3">
                            <?php echo htmlspecialchars($course['code']); ?>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-building me-2"></i>
                                <?php echo htmlspecialchars($course['department_name'] ?? 'Не указано'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-person me-2"></i>
                                <?php echo htmlspecialchars($course['teacher_name'] ?? 'Не указано'); ?>
                                <?php if ($course['teacher_position'] || $course['teacher_degree']): ?>
                                    <div class="small text-muted ms-4">
                                        <?php 
                                        echo htmlspecialchars(
                                            trim(
                                                ($course['teacher_position'] ?? '') . 
                                                ($course['teacher_position'] && $course['teacher_degree'] ? ', ' : '') .
                                                ($course['teacher_degree'] ?? '')
                                            )
                                        ); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-clock me-2"></i>
                                <?php echo $course['credits'] * 36; ?> ак. часов
                            </li>
                            <?php if ($course['description']): ?>
                                <li class="mt-3">
                                    <div class="text-muted">
                                        <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Группы -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Группы</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($groups)): ?>
                            <div class="text-muted">Нет активных групп</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($groups as $group): ?>
                                    <a href="group.php?id=<?php echo $group['id']; ?>" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <div><?php echo htmlspecialchars($group['name']); ?></div>
                                            <small class="text-muted">
                                                <?php echo $group['year_of_study']; ?> курс
                                            </small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $group['students_count']; ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Расписание занятий -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ближайшие занятия</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_lessons)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Нет запланированных занятий
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Время</th>
                                            <th>Тип</th>
                                            <th>Группа</th>
                                            <th>Преподаватель</th>
                                            <th>Аудитория</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_lessons as $lesson): ?>
                                            <tr>
                                                <td>
                                                    <?php echo date('d.m.Y', strtotime($lesson['date'])); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        echo date('H:i', strtotime($lesson['start_time'])) . ' - ' . 
                                                             date('H:i', strtotime($lesson['end_time']));
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getLessonTypeClass($lesson['type']); ?>">
                                                        <?php echo getLessonType($lesson['type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($lesson['group_name']); ?></td>
                                                <td><?php echo htmlspecialchars($lesson['teacher_name']); ?></td>
                                                <td><?php echo htmlspecialchars($lesson['room']); ?></td>
                                                <td class="text-end">
                                                    <?php if ($_SESSION['role_id'] == ROLE_TEACHER): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="teacher_attendance.php?course_id=<?php echo $course_id; ?>&group_id=<?php echo $lesson['group_id']; ?>&date=<?php echo $lesson['date']; ?>" 
                                                               class="btn btn-outline-success" 
                                                               title="Посещаемость">
                                                                <i class="bi bi-calendar-check"></i>
                                                            </a>
                                                            <a href="teacher_students.php?course_id=<?php echo $course_id; ?>&group_id=<?php echo $lesson['group_id']; ?>" 
                                                               class="btn btn-outline-primary" 
                                                               title="Студенты">
                                                                <i class="bi bi-people"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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

function getLessonTypeClass($type) {
    $classes = [
        'lecture' => 'primary',
        'practice' => 'success',
        'lab' => 'info',
        'exam' => 'danger'
    ];
    return $classes[$type] ?? 'secondary';
}
?> 