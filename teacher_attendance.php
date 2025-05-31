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

// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Получаем параметры из URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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
                ) as students_count,
                (
                    SELECT COUNT(DISTINCT l2.id)
                    FROM lessons l2
                    WHERE l2.course_id = c.id AND l2.group_id = g.id
                ) as lessons_count
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
    <title>Учет посещаемости - <?php echo APP_NAME; ?></title>
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
                            <h5 class="card-title mb-0">Учет посещаемости по группам</h5>
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
                                            <th>Занятия</th>
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
                                                    <span class="badge bg-info">
                                                        <?php echo $group['lessons_count']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="teacher_attendance.php?course_id=<?php echo $group['course_id']; ?>&group_id=<?php echo $group['group_id']; ?>" 
                                                           class="btn btn-outline-success" 
                                                           title="Учет посещаемости">
                                                            <i class="bi bi-calendar-check"></i>
                                                        </a>
                                                        <a href="teacher_students.php?course_id=<?php echo $group['course_id']; ?>&group_id=<?php echo $group['group_id']; ?>" 
                                                           class="btn btn-outline-primary" 
                                                           title="Список студентов">
                                                            <i class="bi bi-people"></i>
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

// Обработка формы отметки посещаемости
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    foreach ($_POST['attendance'] as $student_id => $status) {
        $lesson_id = $_POST['lesson_id'];
        
        // Проверяем существующую запись
        $check_sql = "SELECT id FROM attendance WHERE student_id = ? AND lesson_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $student_id, $lesson_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Обновляем существующую запись
            $sql = "UPDATE attendance SET status = ?, updated_at = NOW() WHERE student_id = ? AND lesson_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $status, $student_id, $lesson_id);
        } else {
            // Создаем новую запись
            $sql = "INSERT INTO attendance (student_id, lesson_id, status, date, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $student_id, $lesson_id, $status, $date);
        }
        $stmt->execute();
    }
    
    header("Location: teacher_attendance.php?course_id=$course_id&group_id=$group_id&date=$date&success=1");
    exit;
}

// Получаем информацию о курсе и группе
$sql = "SELECT 
            c.name as course_name,
            c.code as course_code,
            g.name as group_name,
            g.year_of_study,
            d.name as department_name
        FROM courses c
        JOIN lessons l ON c.id = l.course_id
        JOIN `groups` g ON l.group_id = g.id
        LEFT JOIN departments d ON g.department_id = d.id
        WHERE c.id = ? AND g.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса курса: ' . $conn->error);
}

$stmt->bind_param('ii', $course_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();
$course_info = $result->fetch_assoc();

if (!$course_info) {
    header('Location: teacher_courses.php');
    exit;
}

// Получаем занятие на выбранную дату
$sql = "SELECT 
            l.id as lesson_id,
            l.type,
            l.topic,
            l.start_time,
            l.end_time,
            l.room
        FROM lessons l
        WHERE l.course_id = ? 
        AND l.group_id = ? 
        AND l.date = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса занятия: ' . $conn->error);
}

$stmt->bind_param('iis', $course_id, $group_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$lesson = $result->fetch_assoc();

// Получаем список студентов и их посещаемость
$sql = "SELECT 
            s.id as student_id,
            s.student_id_number,
            CONCAT(u.last_name, ' ', u.first_name) as student_name,
            a.status,
            (
                SELECT COUNT(*) 
                FROM attendance a2 
                JOIN lessons l2 ON a2.lesson_id = l2.id 
                WHERE a2.student_id = s.id AND l2.course_id = ?
            ) as total_lessons,
            (
                SELECT COUNT(*) 
                FROM attendance a2 
                JOIN lessons l2 ON a2.lesson_id = l2.id 
                WHERE a2.student_id = s.id AND l2.course_id = ? AND a2.status = 'present'
            ) as present_count,
            (
                SELECT COUNT(*) 
                FROM attendance a2 
                JOIN lessons l2 ON a2.lesson_id = l2.id 
                WHERE a2.student_id = s.id AND l2.course_id = ? AND a2.status = 'late'
            ) as late_count,
            (
                SELECT COUNT(*) 
                FROM attendance a2 
                JOIN lessons l2 ON a2.lesson_id = l2.id 
                WHERE a2.student_id = s.id AND l2.course_id = ? AND a2.status = 'absent'
            ) as absent_count
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN student_groups sg ON s.id = sg.student_id
        LEFT JOIN attendance a ON s.id = a.student_id 
            AND a.lesson_id = ?
        WHERE sg.group_id = ? 
        AND sg.status = 'active'
        ORDER BY u.last_name, u.first_name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса студентов: ' . $conn->error);
}

$lesson_id = $lesson ? $lesson['lesson_id'] : 0;
$stmt->bind_param('iiiiii', $course_id, $course_id, $course_id, $course_id, $lesson_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Получаем даты занятий для календаря
$sql = "SELECT DISTINCT date
        FROM lessons
        WHERE course_id = ? 
        AND group_id = ?
        ORDER BY date";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса дат: ' . $conn->error);
}

$stmt->bind_param('ii', $course_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();
$lesson_dates = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учет посещаемости - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .calendar-date {
            cursor: pointer;
            transition: all 0.2s;
        }
        .calendar-date:hover {
            background-color: #f8f9fa;
        }
        .calendar-date.active {
            background-color: #0d6efd;
            color: white;
        }
        .attendance-form label {
            cursor: pointer;
        }
        .attendance-form input[type="radio"] {
            display: none;
        }
        .attendance-form input[type="radio"] + .btn {
            opacity: 0.5;
        }
        .attendance-form input[type="radio"]:checked + .btn {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                Посещаемость успешно сохранена
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Информация о курсе и календарь -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Информация о курсе</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-book me-2"></i>
                                <?php echo htmlspecialchars($course_info['course_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($course_info['course_code']); ?>)</small>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-people me-2"></i>
                                Группа: <?php echo htmlspecialchars($course_info['group_name']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-calendar3 me-2"></i>
                                Курс: <?php echo $course_info['year_of_study']; ?>
                            </li>
                            <li>
                                <i class="bi bi-building me-2"></i>
                                <?php echo htmlspecialchars($course_info['department_name']); ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Календарь занятий -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Календарь занятий</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($lesson_dates as $lesson_date): ?>
                                <a href="?course_id=<?php echo $course_id; ?>&group_id=<?php echo $group_id; ?>&date=<?php echo $lesson_date['date']; ?>" 
                                   class="calendar-date badge rounded-pill <?php echo $lesson_date['date'] === $date ? 'bg-primary' : 'bg-light text-dark'; ?>">
                                    <?php echo date('d.m', strtotime($lesson_date['date'])); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Форма учета посещаемости -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                Учет посещаемости на <?php echo date('d.m.Y', strtotime($date)); ?>
                            </h5>
                            <div class="btn-group">
                                <a href="teacher_students.php?course_id=<?php echo $course_id; ?>&group_id=<?php echo $group_id; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="bi bi-people"></i> Студенты
                                </a>
                                <a href="teacher_grades.php?course_id=<?php echo $course_id; ?>&group_id=<?php echo $group_id; ?>" 
                                   class="btn btn-outline-success">
                                    <i class="bi bi-journal-check"></i> Оценки
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($lesson): ?>
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="mb-1">Тип занятия:</p>
                                        <h6>
                                            <span class="badge bg-<?php echo getLessonTypeClass($lesson['type']); ?>">
                                                <?php echo getLessonType($lesson['type']); ?>
                                            </span>
                                        </h6>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1">Время:</p>
                                        <h6>
                                            <?php 
                                                echo date('H:i', strtotime($lesson['start_time'])) . ' - ' . 
                                                     date('H:i', strtotime($lesson['end_time']));
                                            ?>
                                        </h6>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1">Аудитория:</p>
                                        <h6><?php echo htmlspecialchars($lesson['room']); ?></h6>
                                    </div>
                                </div>
                                <?php if ($lesson['topic']): ?>
                                    <div class="mt-2">
                                        <p class="mb-1">Тема:</p>
                                        <h6><?php echo htmlspecialchars($lesson['topic']); ?></h6>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="POST" class="attendance-form">
                                <input type="hidden" name="lesson_id" value="<?php echo $lesson['lesson_id']; ?>">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Студент</th>
                                                <th>Номер</th>
                                                <th>Статистика</th>
                                                <th>Статус</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <?php 
                                                    $total = $student['total_lessons'] ?: 1;
                                                    $attendance_rate = round(
                                                        (($student['present_count'] + $student['late_count']) * 100) / $total,
                                                        1
                                                    );
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['student_id_number']); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                                <div class="progress-bar bg-success" 
                                                                     style="width: <?php echo ($student['present_count'] * 100) / $total; ?>%">
                                                                </div>
                                                                <div class="progress-bar bg-warning" 
                                                                     style="width: <?php echo ($student['late_count'] * 100) / $total; ?>%">
                                                                </div>
                                                            </div>
                                                            <span class="badge bg-<?php echo getAttendanceClass($attendance_rate); ?>">
                                                                <?php echo $attendance_rate; ?>%
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <label>
                                                                <input type="radio" 
                                                                       name="attendance[<?php echo $student['student_id']; ?>]" 
                                                                       value="present" 
                                                                       <?php echo $student['status'] === 'present' ? 'checked' : ''; ?>>
                                                                <span class="btn btn-sm btn-outline-success">
                                                                    <i class="bi bi-check-circle"></i>
                                                                </span>
                                                            </label>
                                                            <label>
                                                                <input type="radio" 
                                                                       name="attendance[<?php echo $student['student_id']; ?>]" 
                                                                       value="late"
                                                                       <?php echo $student['status'] === 'late' ? 'checked' : ''; ?>>
                                                                <span class="btn btn-sm btn-outline-warning">
                                                                    <i class="bi bi-clock"></i>
                                                                </span>
                                                            </label>
                                                            <label>
                                                                <input type="radio" 
                                                                       name="attendance[<?php echo $student['student_id']; ?>]" 
                                                                       value="absent"
                                                                       <?php echo $student['status'] === 'absent' ? 'checked' : ''; ?>>
                                                                <span class="btn btn-sm btn-outline-danger">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Сохранить
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                На выбранную дату занятие не запланировано
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
function getAttendanceClass($rate) {
    if ($rate >= 90) return 'success';
    if ($rate >= 70) return 'info';
    if ($rate >= 50) return 'warning';
    return 'danger';
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