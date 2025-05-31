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
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $conn->error);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    die('Ошибка: преподаватель не найден');
}

// Получаем курсы преподавателя
$sql = "SELECT DISTINCT
            c.id as course_id,
            c.name as course_name,
            c.code as course_code,
            c.description,
            c.credits,
            c.semester,
            c.hours,
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
        ORDER BY c.name, g.name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса курсов: ' . $conn->error);
}

$stmt->bind_param('i', $teacher['teacher_id']);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);

// Группируем курсы по группам
$coursesByGroup = [];
foreach ($courses as $course) {
    $groupId = $course['group_id'];
    if (!isset($coursesByGroup[$groupId])) {
        $coursesByGroup[$groupId] = [
            'group_name' => $course['group_name'],
            'courses' => []
        ];
    }
    $coursesByGroup[$groupId]['courses'][] = $course;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои курсы - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
                                <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-award me-2"></i>
                                <?php echo htmlspecialchars($teacher['position']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-mortarboard me-2"></i>
                                <?php echo htmlspecialchars($teacher['academic_degree']); ?>
                            </li>
                            <li>
                                <i class="bi bi-building me-2"></i>
                                <?php echo htmlspecialchars($teacher['department_name']); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Курсы по группам -->
            <div class="col-md-8">
                <?php if (empty($coursesByGroup)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        У вас пока нет активных курсов
                    </div>
                <?php else: ?>
                    <?php foreach ($coursesByGroup as $groupId => $groupData): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-people me-2"></i>
                                    Группа <?php echo htmlspecialchars($groupData['group_name']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Дисциплина</th>
                                                <th>Семестр</th>
                                                <th>Часы</th>
                                                <th>Студенты</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($groupData['courses'] as $course): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                                        <div class="small text-muted">
                                                            <?php echo htmlspecialchars($course['course_code']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $course['semester']; ?></td>
                                                    <td><?php echo $course['hours']; ?></td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?php echo $course['students_count']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="teacher_students.php?course_id=<?php echo $course['course_id']; ?>&group_id=<?php echo $groupId; ?>" 
                                                               class="btn btn-outline-primary" 
                                                               title="Студенты">
                                                                <i class="bi bi-people"></i>
                                                            </a>
                                                            <a href="teacher_attendance.php?course_id=<?php echo $course['course_id']; ?>&group_id=<?php echo $groupId; ?>" 
                                                               class="btn btn-outline-success" 
                                                               title="Посещаемость">
                                                                <i class="bi bi-calendar-check"></i>
                                                            </a>
                                                            <a href="teacher_grades.php?course_id=<?php echo $course['course_id']; ?>&group_id=<?php echo $groupId; ?>" 
                                                               class="btn btn-outline-info" 
                                                               title="Оценки">
                                                                <i class="bi bi-journal-check"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 