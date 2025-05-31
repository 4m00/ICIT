<?php
require_once 'config/config.php';
requireLogin();

// Проверка прав администратора
if ($_SESSION['role_id'] != ROLE_ADMIN) {
    header('Location: dashboard.php');
    exit();
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $sql = "INSERT INTO departments (name, description, head_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $_POST['name'], $_POST['description'], $_POST['head_id']);
                $stmt->execute();
                break;

            case 'update':
                $sql = "UPDATE departments SET name = ?, description = ?, head_id = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $_POST['name'], $_POST['description'], $_POST['head_id'], $_POST['department_id']);
                $stmt->execute();
                break;

            case 'delete':
                // Проверяем, есть ли связанные записи
                $sql = "SELECT COUNT(*) as count FROM teachers WHERE department_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $_POST['department_id']);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();

                if ($result['count'] > 0) {
                    $_SESSION['error'] = 'Невозможно удалить кафедру, так как есть связанные преподаватели';
                } else {
                    $sql = "DELETE FROM departments WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_POST['department_id']);
                    $stmt->execute();
                }
                break;
        }

        // Логируем действие
        $action = $_POST['action'] . ' department';
        $sql = "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("iss", $_SESSION['user_id'], $action, $ip);
        $stmt->execute();

        header('Location: departments.php');
        exit();
    }
}

// Получаем список преподавателей для выбора заведующего
if (!defined('ROLE_TEACHER')) {
    define('ROLE_TEACHER', 2);
}

$sql = "SELECT u.id, u.first_name, u.last_name, t.position 
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN teachers t ON u.id = t.user_id
        WHERE ur.role_id = ?
        ORDER BY u.last_name, u.first_name";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$teacher_role = ROLE_TEACHER;
$stmt->bind_param("i", $teacher_role);
if (!$stmt->execute()) {
    die("Ошибка выполнения запроса: " . $stmt->error);
}
$result = $stmt->get_result();
if (!$result) {
    die("Ошибка получения результата: " . $stmt->error);
}
$teachers = $result->fetch_all(MYSQLI_ASSOC);

// Получаем список кафедр с информацией о заведующих
$sql = "SELECT d.*, 
        u.first_name as head_first_name, 
        u.last_name as head_last_name,
        (SELECT COUNT(*) FROM teachers WHERE department_id = d.id) as teachers_count,
        (SELECT COUNT(*) FROM student_groups WHERE department_id = d.id) as groups_count
        FROM departments d
        LEFT JOIN users u ON d.head_id = u.id
        ORDER BY d.name";

$result = $conn->query($sql);
if (!$result) {
    die("Ошибка запроса: " . $conn->error);
}
$departments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление кафедрами - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление кафедрами</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
                <i class="bi bi-plus-lg"></i> Добавить кафедру
            </button>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Таблица кафедр -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="departmentsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Заведующий</th>
                            <th>Статистика</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo $dept['id']; ?></td>
                                <td><?php echo $dept['name']; ?></td>
                                <td><?php echo $dept['description']; ?></td>
                                <td>
                                    <?php 
                                        if ($dept['head_id']) {
                                            echo $dept['head_last_name'] . ' ' . $dept['head_first_name'];
                                        } else {
                                            echo '<span class="text-muted">Не назначен</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="bi bi-people me-1"></i> <?php echo $dept['teachers_count']; ?> преподавателей<br>
                                        <i class="bi bi-collection me-1"></i> <?php echo $dept['groups_count']; ?> групп
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editDepartment(<?php echo htmlspecialchars(json_encode($dept)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteDepartment(<?php echo $dept['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Модальное окно создания кафедры -->
    <div class="modal fade" id="createDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить кафедру</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createDepartmentForm" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Заведующий кафедрой</label>
                            <select class="form-select" name="head_id">
                                <option value="">Выберите заведующего</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo $teacher['last_name'] . ' ' . $teacher['first_name'] . 
                                                 ' (' . $teacher['position'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="createDepartmentForm" class="btn btn-primary">Создать</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования кафедры -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать кафедру</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editDepartmentForm" method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="department_id" id="editDepartmentId">
                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Заведующий кафедрой</label>
                            <select class="form-select" name="head_id" id="editHeadId">
                                <option value="">Выберите заведующего</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo $teacher['last_name'] . ' ' . $teacher['first_name'] . 
                                                 ' (' . $teacher['position'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="editDepartmentForm" class="btn btn-primary">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить эту кафедру?</p>
                    <form id="deleteDepartmentForm" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="department_id" id="deleteDepartmentId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="deleteDepartmentForm" class="btn btn-danger">Удалить</button>
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
            $('#departmentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
                }
            });
        });

        function editDepartment(department) {
            document.getElementById('editDepartmentId').value = department.id;
            document.getElementById('editName').value = department.name;
            document.getElementById('editDescription').value = department.description;
            document.getElementById('editHeadId').value = department.head_id || '';
            
            new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
        }

        function deleteDepartment(departmentId) {
            document.getElementById('deleteDepartmentId').value = departmentId;
            new bootstrap.Modal(document.getElementById('deleteDepartmentModal')).show();
        }
    </script>
</body>
</html> 