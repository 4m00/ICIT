<?php
require_once '../config/config.php';
requireLogin();

if (!isset($_GET['id'])) {
    die('ID студента не указан');
}

$student_id = (int)$_GET['id'];

// Получаем информацию о студенте
$sql = "SELECT u.first_name, u.last_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die('Студент не найден');
}

// Получаем данные о посещаемости
$sql = "SELECT a.*, c.name as course_name, c.code as course_code,
               l.topic, l.type as lesson_type
        FROM attendance a
        JOIN lessons l ON a.lesson_id = l.id
        JOIN courses c ON l.course_id = c.id
        WHERE a.student_id = ?
        ORDER BY a.date DESC, l.start_time DESC
        LIMIT 50"; // Ограничиваем последними 50 записями

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_all(MYSQLI_ASSOC);

// Получаем статистику посещаемости
$sql = "SELECT 
            COUNT(*) as total_lessons,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM attendance
        WHERE student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Рассчитываем процент посещаемости
$attendance_rate = $stats['total_lessons'] > 0 
    ? round(($stats['present_count'] + $stats['late_count'] * 0.5) / $stats['total_lessons'] * 100, 1)
    : 0;
?>

<h4 class="mb-4">Посещаемость: <?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></h4>

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h6 class="card-title">Всего занятий</h6>
                <h3 class="mb-0"><?php echo $stats['total_lessons']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6 class="card-title">Присутствовал</h6>
                <h3 class="mb-0"><?php echo $stats['present_count']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h6 class="card-title">Опоздания</h6>
                <h3 class="mb-0"><?php echo $stats['late_count']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h6 class="card-title">Пропуски</h6>
                <h3 class="mb-0"><?php echo $stats['absent_count']; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Процент посещаемости -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Общий процент посещаемости</h5>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-<?php echo getAttendanceClass($attendance_rate); ?>" 
                         role="progressbar" 
                         style="width: <?php echo $attendance_rate; ?>%"
                         aria-valuenow="<?php echo $attendance_rate; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?php echo $attendance_rate; ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Таблица посещаемости -->
<?php if (empty($attendance)): ?>
    <div class="alert alert-info">
        Нет данных о посещаемости
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Дисциплина</th>
                    <th>Тема</th>
                    <th>Тип занятия</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $record): ?>
                    <tr>
                        <td><?php echo date('d.m.Y', strtotime($record['date'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars($record['course_name']); ?>
                            <div class="small text-muted"><?php echo htmlspecialchars($record['course_code']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($record['topic']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo getLessonTypeClass($record['lesson_type']); ?>">
                                <?php echo getLessonTypeName($record['lesson_type']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo getStatusClass($record['status']); ?>">
                                <?php echo getStatusName($record['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
function getAttendanceClass($rate) {
    if ($rate >= 90) return 'success';
    if ($rate >= 70) return 'info';
    if ($rate >= 50) return 'warning';
    return 'danger';
}

function getStatusClass($status) {
    $classes = [
        'present' => 'success',
        'absent' => 'danger',
        'late' => 'warning'
    ];
    return $classes[$status] ?? 'secondary';
}

function getStatusName($status) {
    $names = [
        'present' => 'Присутствовал',
        'absent' => 'Отсутствовал',
        'late' => 'Опоздал'
    ];
    return $names[$status] ?? 'Неизвестно';
}

function getLessonTypeClass($type) {
    $classes = [
        'lecture' => 'primary',
        'practice' => 'success',
        'consultation' => 'info',
        'exam' => 'danger'
    ];
    return $classes[$type] ?? 'secondary';
}

function getLessonTypeName($type) {
    $names = [
        'lecture' => 'Лекция',
        'practice' => 'Практика',
        'consultation' => 'Консультация',
        'exam' => 'Экзамен'
    ];
    return $names[$type] ?? 'Неизвестно';
}
?> 