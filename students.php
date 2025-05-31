<?php
require_once 'config/config.php';
requireLogin();

// Проверка прав доступа
if ($_SESSION['role_id'] != ROLE_ADMIN && $_SESSION['role_id'] != ROLE_DEKANAT) {
    header('Location: dashboard.php');
    exit();
}

// Получаем параметры фильтрации
$where_conditions = [];
$params = [];
$param_types = '';

// Фильтр по группе
if (!empty($_GET['group_id'])) {
    $where_conditions[] = "sg.group_id = ?";
    $params[] = $_GET['group_id'];
    $param_types .= 'i';
}

// Фильтр по кафедре
if (!empty($_GET['department_id'])) {
    $where_conditions[] = "g.department_id = ?";
    $params[] = $_GET['department_id'];
    $param_types .= 'i';
}

// Фильтр по статусу
if (!empty($_GET['status'])) {
    $where_conditions[] = "s.status = ?";
    $params[] = $_GET['status'];
    $param_types .= 's';
}

// Формируем базовый SQL запрос
$sql = "SELECT s.*, u.first_name, u.last_name, u.email, g.name as group_name, d.name as department_name
        FROM students s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN student_groups sg ON s.id = sg.student_id
        LEFT JOIN `groups` g ON sg.group_id = g.id
        LEFT JOIN departments d ON g.department_id = d.id";

// Добавляем условия фильтрации, если они есть
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY u.last_name, u.first_name";

// Подготавливаем и выполняем запрос
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    
    // Привязываем параметры динамически
    $bind_params = array($param_types);
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
    if (!$result) {
        die("Ошибка запроса: " . $conn->error);
    }
}

$students = $result->fetch_all(MYSQLI_ASSOC);

// Получаем список групп для фильтрации
$sql = "SELECT id, name FROM `groups` ORDER BY name";
$result = $conn->query($sql);
if (!$result) {
    die("Ошибка запроса групп: " . $conn->error);
}
$groups = $result->fetch_all(MYSQLI_ASSOC);

// Получаем список кафедр для фильтрации
$sql = "SELECT id, name FROM departments ORDER BY name";
$result = $conn->query($sql);
if (!$result) {
    die("Ошибка запроса кафедр: " . $conn->error);
}
$departments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление студентами - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление студентами</h1>
            <?php if ($_SESSION['role_id'] == ROLE_ADMIN): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="bi bi-plus-lg"></i> Добавить студента
            </button>
            <?php endif; ?>
        </div>

        <!-- Фильтры -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3" method="GET">
                    <div class="col-md-3">
                        <label class="form-label">Группа</label>
                        <select class="form-select" name="group_id">
                            <option value="">Все группы</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['id']; ?>" <?php echo isset($_GET['group_id']) && $_GET['group_id'] == $group['id'] ? 'selected' : ''; ?>>
                                    <?php echo $group['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Кафедра</label>
                        <select class="form-select" name="department_id">
                            <option value="">Все кафедры</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo isset($_GET['department_id']) && $_GET['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Статус</label>
                        <select class="form-select" name="status">
                            <option value="">Все статусы</option>
                            <?php 
                            $statuses = [
                                'active' => 'Активные',
                                'inactive' => 'Неактивные',
                                'academic_leave' => 'Академический отпуск',
                                'graduated' => 'Выпускники'
                            ];
                            foreach ($statuses as $value => $label): 
                            ?>
                                <option value="<?php echo $value; ?>" <?php echo isset($_GET['status']) && $_GET['status'] == $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-search"></i> Применить
                            </button>
                            <a href="students.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Сбросить
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Таблица студентов -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="studentsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Группа</th>
                            <th>Кафедра</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($student['email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($student['group_name'] ?? 'Не назначена'); ?></td>
                                <td><?php echo htmlspecialchars($student['department_name'] ?? 'Не назначена'); ?></td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                        'academic_leave' => 'warning',
                                        'graduated' => 'info'
                                    ];
                                    $statusNames = [
                                        'active' => 'Активный',
                                        'inactive' => 'Неактивный',
                                        'academic_leave' => 'Академический отпуск',
                                        'graduated' => 'Выпускник'
                                    ];
                                    $statusClass = $statusClasses[$student['status']] ?? 'secondary';
                                    $statusName = $statusNames[$student['status']] ?? 'Неизвестно';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo $statusName; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button"
                                           class="btn btn-sm btn-outline-primary btn-profile"
                                           data-student-id="<?php echo $student['id']; ?>"
                                           title="Профиль">
                                            <i class="bi bi-person"></i>
                                        </button>
                                        <button type="button"
                                           class="btn btn-sm btn-outline-success btn-grades"
                                           data-student-id="<?php echo $student['id']; ?>"
                                           title="Успеваемость">
                                            <i class="bi bi-mortarboard"></i>
                                        </button>
                                        <button type="button"
                                           class="btn btn-sm btn-outline-info btn-attendance"
                                           data-student-id="<?php echo $student['id']; ?>"
                                           title="Посещаемость">
                                            <i class="bi bi-calendar-check"></i>
                                        </button>
                                        <?php if ($_SESSION['role_id'] == ROLE_ADMIN): ?>
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                                                title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Модальное окно для профиля студента -->
    <div class="modal fade" id="studentProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Профиль студента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentProfileContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для успеваемости -->
    <div class="modal fade" id="studentGradesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Успеваемость студента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentGradesContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для посещаемости -->
    <div class="modal fade" id="studentAttendanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Посещаемость студента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentAttendanceContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Инициализация DataTables
            var table = $('#studentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
                },
                order: [[0, 'asc']], // Сортировка по фамилии по умолчанию
                pageLength: 25 // Количество записей на странице
            });

            // Обработчики для модальных окон
            function loadModalContent(url, modalId) {
                $(`#${modalId}Content`).html(`
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                    </div>
                `);
                
                $.get(url, function(data) {
                    $(`#${modalId}Content`).html(data);
                }).fail(function() {
                    $(`#${modalId}Content`).html(`
                        <div class="alert alert-danger">
                            Произошла ошибка при загрузке данных. Пожалуйста, попробуйте позже.
                        </div>
                    `);
                });
            }

            // Обработчик для кнопки профиля
            $(document).on('click', '.btn-profile', function(e) {
                e.preventDefault();
                const studentId = $(this).data('student-id');
                loadModalContent(`ajax/student_profile.php?id=${studentId}`, 'studentProfile');
                $('#studentProfileModal').modal('show');
            });

            // Обработчик для кнопки успеваемости
            $(document).on('click', '.btn-grades', function(e) {
                e.preventDefault();
                const studentId = $(this).data('student-id');
                loadModalContent(`ajax/student_grades.php?id=${studentId}`, 'studentGrades');
                $('#studentGradesModal').modal('show');
            });

            // Обработчик для кнопки посещаемости
            $(document).on('click', '.btn-attendance', function(e) {
                e.preventDefault();
                const studentId = $(this).data('student-id');
                loadModalContent(`ajax/student_attendance.php?id=${studentId}`, 'studentAttendance');
                $('#studentAttendanceModal').modal('show');
            });
        });

        // Функция для редактирования студента
        function editStudent(student) {
            // Здесь будет код для редактирования
            console.log('Редактирование студента:', student);
        }
    </script>
</body>
</html> 