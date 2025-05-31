<?php
require_once 'config/config.php';
requireLogin();

// Включаем отображение всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверяем, что пользователь - студент
if ($_SESSION['role_id'] != ROLE_STUDENT) {
    header('Location: dashboard.php');
    exit;
}

// Получаем ID студента
$user_id = $_SESSION['user_id'];

// Отладочная информация о сессии
echo "<!-- Debug Session Info:
User ID: " . $_SESSION['user_id'] . "
Role ID: " . $_SESSION['role_id'] . "
Username: " . $_SESSION['username'] . "
-->";

// Получаем информацию о студенте
$sql = "SELECT 
            s.id as student_id, 
            s.student_id_number, 
            s.current_semester,
            s.faculty_id,
            f.name as faculty_name,
            g.id as group_id,
            g.name as group_name,
            d.id as department_id, 
            d.name as department_name
        FROM students s
        LEFT JOIN faculties f ON s.faculty_id = f.id
        LEFT JOIN student_groups sg ON s.id = sg.student_id
        LEFT JOIN `groups` g ON sg.group_id = g.id
        LEFT JOIN departments d ON g.department_id = d.id
        WHERE s.user_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса студента: ' . $conn->error . '<br>SQL: ' . $sql);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die('Ошибка: студент не найден. User ID: ' . $user_id);
}

// Отладочная информация о студенте
echo "<!-- Debug Student Info:
Student ID: " . $student['student_id'] . "
Student Number: " . $student['student_id_number'] . "
Faculty ID: " . $student['faculty_id'] . "
Faculty Name: " . $student['faculty_name'] . "
Group ID: " . $student['group_id'] . "
Group Name: " . $student['group_name'] . "
Department ID: " . $student['department_id'] . "
Department Name: " . $student['department_name'] . "
Current Semester: " . $student['current_semester'] . "
-->";

// Проверяем наличие группы
if (!$student['group_id']) {
    // Если группа не найдена, пробуем получить её через student_group_members
    $sql = "SELECT 
                g.id as group_id,
                g.name as group_name,
                d.id as department_id,
                d.name as department_name
            FROM student_group_members sgm
            JOIN `groups` g ON sgm.group_id = g.id
            LEFT JOIN departments d ON g.department_id = d.id
            WHERE sgm.student_id = ? AND sgm.status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $student['student_id']);
    $stmt->execute();
    $groupResult = $stmt->get_result();
    
    if ($groupData = $groupResult->fetch_assoc()) {
        $student['group_id'] = $groupData['group_id'];
        $student['group_name'] = $groupData['group_name'];
        $student['department_id'] = $groupData['department_id'];
        $student['department_name'] = $groupData['department_name'];
    }
}

// Получаем оценки по всем предметам
$sql = "SELECT 
            g.id,
            g.student_id,
            g.course_id,
            g.grade,
            g.comment,
            g.created_at,
            c.name as course_name,
            c.code as course_code,
            CONCAT(u.last_name, ' ', u.first_name) as teacher_name
        FROM grades g
        JOIN courses c ON g.course_id = c.id
        JOIN teachers t ON g.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE g.student_id = (SELECT id FROM students WHERE user_id = ?)
        ORDER BY g.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $conn->error . '<br>SQL: ' . $sql);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$grades = $result->fetch_all(MYSQLI_ASSOC);

// Группируем оценки по предметам
$gradesByCourse = [];
foreach ($grades as $grade) {
    $courseId = $grade['course_id'];
    if (!isset($gradesByCourse[$courseId])) {
        $gradesByCourse[$courseId] = [
            'course_name' => $grade['course_name'],
            'course_code' => $grade['course_code'],
            'teacher_name' => $grade['teacher_name'],
            'grades' => [],
            'average_grade' => 0,
            'grades_count' => 0,
            'dates' => [],
            'comments' => []
        ];
    }
    $gradesByCourse[$courseId]['grades'][] = $grade['grade'];
    $gradesByCourse[$courseId]['dates'][] = $grade['created_at'];
    $gradesByCourse[$courseId]['comments'][] = $grade['comment'];
    $gradesByCourse[$courseId]['grades_count']++;
}

// Вычисляем средний балл для каждого предмета
foreach ($gradesByCourse as $courseId => &$courseData) {
    $courseData['average_grade'] = array_sum($courseData['grades']) / count($courseData['grades']);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оценки - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
                                Номер студента: <?php echo htmlspecialchars($student['student_id_number']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-people me-2"></i>
                                Группа: <?php echo htmlspecialchars($student['group_name']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-building me-2"></i>
                                Кафедра: <?php echo htmlspecialchars($student['department_name']); ?>
                            </li>
                            <li>
                                <i class="bi bi-mortarboard me-2"></i>
                                Текущий семестр: <?php echo $student['current_semester']; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Оценки по предметам -->
            <div class="col-md-8">
                <?php if (empty($gradesByCourse)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Нет доступных оценок
                    </div>
                <?php else: ?>
                    <?php foreach ($gradesByCourse as $courseId => $courseData): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($courseData['course_name']); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($courseData['course_code']); ?>)</small>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1">Преподаватель:</p>
                                        <h6><?php echo htmlspecialchars($courseData['teacher_name']); ?></h6>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1">Средний балл:</p>
                                        <h6>
                                            <span class="badge bg-<?php echo getGradeClass($courseData['average_grade']); ?>">
                                                <?php echo number_format($courseData['average_grade'], 1); ?>
                                            </span>
                                        </h6>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Оценка</th>
                                                <th>Комментарий</th>
                                                <th>Дата</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($courseData['grades'] as $index => $grade): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php echo getGradeClass($grade); ?>">
                                                            <?php echo $grade; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($courseData['comments'][$index] ?? ''); ?></td>
                                                    <td><?php echo date('d.m.Y', strtotime($courseData['dates'][$index])); ?></td>
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

<?php
function getGradeClass($grade) {
    if ($grade >= 90) return 'success';
    if ($grade >= 70) return 'info';
    if ($grade >= 50) return 'warning';
    return 'danger';
}

function getAttendanceClass($rate) {
    if ($rate >= 90) return 'success';
    if ($rate >= 70) return 'info';
    if ($rate >= 50) return 'warning';
    return 'danger';
}
?> 