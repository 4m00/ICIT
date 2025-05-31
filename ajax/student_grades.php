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

// Получаем оценки по курсам
$sql = "SELECT ap.*, c.name as course_name, c.code as course_code,
               t.first_name as teacher_first_name, t.last_name as teacher_last_name
        FROM academic_performance ap
        JOIN courses c ON ap.course_id = c.id
        LEFT JOIN users t ON c.teacher_id = t.id
        WHERE ap.student_id = ?
        ORDER BY ap.academic_year DESC, ap.semester DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$grades = $result->fetch_all(MYSQLI_ASSOC);
?>

<h4 class="mb-4">Успеваемость: <?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></h4>

<?php if (empty($grades)): ?>
    <div class="alert alert-info">
        Нет данных об успеваемости
    </div>
<?php else: ?>
    <?php
    // Группируем оценки по учебному году и семестру
    $grouped_grades = [];
    foreach ($grades as $grade) {
        $key = $grade['academic_year'] . '_' . $grade['semester'];
        if (!isset($grouped_grades[$key])) {
            $grouped_grades[$key] = [
                'year' => $grade['academic_year'],
                'semester' => $grade['semester'],
                'grades' => []
            ];
        }
        $grouped_grades[$key]['grades'][] = $grade;
    }
    ?>

    <div class="accordion" id="gradesAccordion">
        <?php foreach ($grouped_grades as $key => $period): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?php echo $key === array_key_first($grouped_grades) ? '' : 'collapsed'; ?>" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#collapse<?php echo $key; ?>">
                        <?php echo $period['year'] . ' - Семестр ' . $period['semester']; ?>
                    </button>
                </h2>
                <div id="collapse<?php echo $key; ?>" 
                     class="accordion-collapse collapse <?php echo $key === array_key_first($grouped_grades) ? 'show' : ''; ?>"
                     data-bs-parent="#gradesAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Дисциплина</th>
                                        <th>Преподаватель</th>
                                        <th>Итоговая оценка</th>
                                        <th>Посещаемость</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($period['grades'] as $grade): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($grade['course_name']); ?>
                                                <div class="small text-muted"><?php echo htmlspecialchars($grade['course_code']); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($grade['teacher_last_name']) {
                                                    echo htmlspecialchars($grade['teacher_last_name'] . ' ' . $grade['teacher_first_name']);
                                                } else {
                                                    echo 'Не назначен';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($grade['final_grade']): ?>
                                                    <span class="badge bg-<?php echo getGradeClass($grade['final_grade']); ?>">
                                                        <?php echo number_format($grade['final_grade'], 1); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Нет оценки</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($grade['attendance_rate']): ?>
                                                    <span class="badge bg-<?php echo getAttendanceClass($grade['attendance_rate']); ?>">
                                                        <?php echo number_format($grade['attendance_rate'], 1); ?>%
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Нет данных</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

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