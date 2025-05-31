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

// Получаем параметры из URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

// Если не указаны course_id и group_id, показываем список всех групп преподавателя
if (!$course_id || !$group_id) {
    // Получаем все группы преподавателя
    $sql = "SELECT DISTINCT
                c.id as course_id,
                c.name as course_name,
                c.code as course_code,
                g.id as group_id,
                g.name as group_name,
                g.year_of_study,
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
            ORDER BY g.name, c.name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $teacher['teacher_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $groups = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои группы - <?php echo APP_NAME; ?></title>
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

            <!-- Список групп -->
            <div class="col-md-8">
                <?php if (empty($groups)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        У вас пока нет активных групп
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Мои группы</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Группа</th>
                                            <th>Курс</th>
                                            <th>Дисциплина</th>
                                            <th>Студенты</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($groups as $group): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($group['group_name']); ?></td>
                                                <td><?php echo $group['year_of_study']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($group['course_name']); ?>
                                                    <div class="small text-muted">
                                                        <?php echo htmlspecialchars($group['course_code']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo $group['students_count']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="teacher_students.php?course_id=<?php echo $group['course_id']; ?>&group_id=<?php echo $group['group_id']; ?>" 
                                                           class="btn btn-outline-primary" 
                                                           title="Список студентов">
                                                            <i class="bi bi-people"></i>
                                                        </a>
                                                        <a href="teacher_attendance.php?course_id=<?php echo $group['course_id']; ?>&group_id=<?php echo $group['group_id']; ?>" 
                                                           class="btn btn-outline-success" 
                                                           title="Посещаемость">
                                                            <i class="bi bi-calendar-check"></i>
                                                        </a>
                                                        <a href="teacher_grades.php?course_id=<?php echo $group['course_id']; ?>&group_id=<?php echo $group['group_id']; ?>" 
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    exit;
}

// Если указаны course_id и group_id, показываем список студентов конкретной группы
// Получаем информацию о курсе и группе
$sql = "SELECT 
            c.name as course_name,
            c.code as course_code,
            g.name as group_name,
            g.year_of_study
        FROM courses c
        JOIN `groups` g ON g.id = ?
        WHERE c.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $group_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$group_info = $result->fetch_assoc();

if (!$group_info) {
    header('Location: teacher_students.php');
    exit;
}

// Получаем список студентов группы
$sql = "SELECT 
            s.id as student_id,
            s.student_id_number,
            CONCAT(u.last_name, ' ', u.first_name) as student_name,
            u.email,
            s.phone,
            s.enrollment_year
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN student_groups sg ON s.id = sg.student_id
        WHERE sg.group_id = ? 
        AND sg.status = 'active'
        ORDER BY u.last_name, u.first_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $group_id);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список студентов - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                Группа <?php echo htmlspecialchars($group_info['group_name']); ?>
                                <small class="text-muted">
                                    (<?php echo htmlspecialchars($group_info['course_name']); ?>)
                                </small>
                            </h5>
                            <div class="btn-group">
                                <a href="teacher_attendance.php?course_id=<?php echo $course_id; ?>&group_id=<?php echo $group_id; ?>" 
                                   class="btn btn-outline-success">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Посещаемость
                                </a>
                                <a href="teacher_grades.php?course_id=<?php echo $course_id; ?>&group_id=<?php echo $group_id; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="bi bi-journal-check me-1"></i>
                                    Оценки
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                В группе пока нет студентов
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>№</th>
                                            <th>ФИО</th>
                                            <th>Номер студента</th>
                                            <th>Email</th>
                                            <th>Телефон</th>
                                            <th>Год поступления</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $index => $student): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['student_id_number']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td><?php echo $student['enrollment_year']; ?></td>
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