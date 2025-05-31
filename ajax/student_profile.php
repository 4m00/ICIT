<?php
require_once '../config/config.php';
requireLogin();

if (!isset($_GET['id'])) {
    die('ID студента не указан');
}

$student_id = (int)$_GET['id'];

// Получаем данные о студенте
$sql = "SELECT s.*, u.first_name, u.last_name, u.email, 
               g.name as group_name, d.name as department_name
        FROM students s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN student_groups sg ON s.id = sg.student_id
        LEFT JOIN `groups` g ON sg.group_id = g.id
        LEFT JOIN departments d ON g.department_id = d.id
        WHERE s.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $conn->error);
}

$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die('Студент не найден');
}

// Форматируем статус
$statusNames = [
    'active' => 'Активный',
    'inactive' => 'Неактивный',
    'academic_leave' => 'Академический отпуск',
    'graduated' => 'Выпускник'
];
?>

<div class="row">
    <div class="col-md-4">
        <div class="text-center mb-3">
            <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
        </div>
    </div>
    <div class="col-md-8">
        <h4><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></h4>
        <p class="text-muted"><?php echo htmlspecialchars($student['email']); ?></p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <h5>Основная информация</h5>
        <table class="table">
            <tr>
                <th>Статус:</th>
                <td><span class="badge bg-<?php echo getStatusClass($student['status']); ?>">
                    <?php echo $statusNames[$student['status']] ?? 'Неизвестно'; ?>
                </span></td>
            </tr>
            <tr>
                <th>Студ. билет:</th>
                <td><?php echo htmlspecialchars($student['student_id_number']); ?></td>
            </tr>
            <tr>
                <th>Дата зачисления:</th>
                <td><?php echo date('d.m.Y', strtotime($student['enrollment_date'])); ?></td>
            </tr>
            <tr>
                <th>Семестр:</th>
                <td><?php echo $student['current_semester']; ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>Учебная информация</h5>
        <table class="table">
            <tr>
                <th>Факультет:</th>
                <td><?php 
                    // Получаем факультет через кафедру
                    $faculty_name = '';
                    if ($student['department_name']) {
                        $sql = "SELECT f.name FROM departments d 
                               JOIN faculties f ON d.faculty_id = f.id 
                               WHERE d.name = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param('s', $student['department_name']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                $faculty_name = $row['name'];
                            }
                        }
                    }
                    echo htmlspecialchars($faculty_name ?: 'Не назначен');
                ?></td>
            </tr>
            <tr>
                <th>Кафедра:</th>
                <td><?php echo htmlspecialchars($student['department_name'] ?? 'Не назначена'); ?></td>
            </tr>
            <tr>
                <th>Группа:</th>
                <td><?php echo htmlspecialchars($student['group_name'] ?? 'Не назначена'); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php
function getStatusClass($status) {
    $statusClasses = [
        'active' => 'success',
        'inactive' => 'danger',
        'academic_leave' => 'warning',
        'graduated' => 'info'
    ];
    return $statusClasses[$status] ?? 'secondary';
}
?> 