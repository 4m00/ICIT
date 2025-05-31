<?php
require_once 'config/config.php';
requireLogin();

// Проверка подключения к базе данных
if (!$conn) {
    die("Ошибка подключения к базе данных");
}

// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка прав администратора
if ($_SESSION['role_id'] != ROLE_ADMIN) {
    header('Location: dashboard.php');
    exit();
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    // Выводим данные формы для отладки
                    echo "<pre>POST data: ";
                    print_r($_POST);
                    echo "</pre>";
                    
                    // Проверяем обязательные поля
                    if (empty($_POST['first_name']) || empty($_POST['last_name']) || 
                        empty($_POST['login']) || empty($_POST['password']) || 
                        empty($_POST['role_id'])) {
                        throw new Exception('Все обязательные поля должны быть заполнены');
                    }

                    // Начинаем транзакцию
                    $conn->begin_transaction();

                    try {
                        // Создаем пользователя
                        $sql = "INSERT INTO users (username, password, email, first_name, last_name, is_active) 
                                VALUES (?, ?, ?, ?, ?, 1)";
                        error_log("SQL запрос для создания пользователя: " . $sql);
                        
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            throw new Exception("Ошибка подготовки запроса создания пользователя: " . $conn->error);
                        }

                        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $email = $_POST['email'] ?? '';
                        
                        error_log("Binding parameters for user creation: login=" . $_POST['login'] . 
                                ", email=" . $email . 
                                ", first_name=" . $_POST['first_name'] . 
                                ", last_name=" . $_POST['last_name']);
                        
                        $stmt->bind_param("sssss", 
                            $_POST['login'],
                            $password_hash,
                            $email,
                            $_POST['first_name'],
                            $_POST['last_name']
                        );
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Ошибка создания пользователя: " . $stmt->error);
                        }
                        
                        $user_id = $conn->insert_id;
                        error_log("Пользователь создан с ID: " . $user_id);

                        // Добавляем роль пользователя
                        $sql = "INSERT INTO user_roles (user_id, role_id, role_type) VALUES (?, ?, 'primary')";
                        error_log("SQL запрос для назначения роли: " . $sql);
                        
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            throw new Exception("Ошибка подготовки запроса ролей: " . $conn->error);
                        }
                        
                        error_log("Binding parameters for role assignment: user_id=" . $user_id . 
                                ", role_id=" . $_POST['role_id']);
                        
                        $stmt->bind_param("ii", $user_id, $_POST['role_id']);
                        if (!$stmt->execute()) {
                            throw new Exception("Ошибка назначения роли: " . $stmt->error);
                        }
                        error_log("Роль назначена успешно");

                        // Если это студент или преподаватель, создаем дополнительные записи
                        if ($_POST['role_id'] == ROLE_STUDENT) {
                            $sql = "INSERT INTO students (user_id, student_id_number, enrollment_date, current_semester, status) 
                                    VALUES (?, ?, CURDATE(), 1, 'active')";
                            error_log("SQL запрос для создания студента: " . $sql);
                            
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                throw new Exception("Ошибка подготовки запроса студента: " . $conn->error);
                            }
                            
                            $student_id = $_POST['student_id'] ?? $_POST['login'];
                            error_log("Binding parameters for student creation: user_id=" . $user_id . 
                                    ", student_id=" . $student_id);
                            
                            $stmt->bind_param("is", $user_id, $student_id);
                            if (!$stmt->execute()) {
                                throw new Exception("Ошибка создания студента: " . $stmt->error);
                            }
                        } 
                        elseif ($_POST['role_id'] == ROLE_TEACHER) {
                            if (empty($_POST['department_id']) || empty($_POST['position'])) {
                                throw new Exception('Для преподавателя необходимо указать кафедру и должность');
                            }
                            
                            // Создаем запись преподавателя
                            $sql = "INSERT INTO teachers (user_id, department_id, department, position, employment_date) 
                                    VALUES (?, ?, (SELECT name FROM departments WHERE id = ?), ?, CURDATE())";
                            error_log("SQL запрос для создания преподавателя: " . $sql);
                            
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                throw new Exception("Ошибка подготовки запроса преподавателя: " . $conn->error);
                            }
                            
                            error_log("Binding parameters for teacher creation: user_id=" . $user_id . 
                                    ", department_id=" . $_POST['department_id'] . 
                                    ", position=" . $_POST['position']);
                            
                            $stmt->bind_param("iiis", 
                                $user_id, 
                                $_POST['department_id'],
                                $_POST['department_id'],
                                $_POST['position']
                            );
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Ошибка создания преподавателя: " . $stmt->error);
                            }
                        }

                        // Если все успешно, фиксируем транзакцию
                        $conn->commit();
                        $_SESSION['success'] = 'Пользователь успешно создан';
                        error_log("Транзакция зафиксирована успешно");

                    } catch (Exception $e) {
                        error_log("Ошибка при создании пользователя: " . $e->getMessage());
                        $conn->rollback();
                        $_SESSION['error'] = $e->getMessage();
                    }
                    break;

                case 'update':
                    $sql = "UPDATE users SET first_name = ?, last_name = ?, login = ?, role_id = ?, is_active = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    $stmt->bind_param("sssiii", 
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['login'],
                        $_POST['role_id'],
                        $is_active,
                        $_POST['user_id']
                    );
                    $stmt->execute();

                    // Обновляем пароль, если он был изменен
                    if (!empty($_POST['password'])) {
                        $sql = "UPDATE users SET password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt->bind_param("si", $password_hash, $_POST['user_id']);
                        $stmt->execute();
                    }
                    break;

                case 'delete':
                    // Сначала удаляем связанные записи
                    $sql = "DELETE FROM students WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_POST['user_id']);
                    $stmt->execute();

                    $sql = "DELETE FROM teachers WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_POST['user_id']);
                    $stmt->execute();

                    // Затем удаляем пользователя
                    $sql = "DELETE FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_POST['user_id']);
                    $stmt->execute();
                    break;
            }
            
            // Логируем действие
            $action = $_POST['action'] . ' user';
            $sql = "INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("iss", $_SESSION['user_id'], $action, $ip);
            $stmt->execute();
        } catch (Exception $e) {
            // В случае ошибки откатываем транзакцию
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: users.php');
        exit();
    }
}

// Получаем список кафедр для формы
$sql = "SELECT id, name FROM departments ORDER BY name";
$departments = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Получаем список пользователей с дополнительной информацией
$sql = "SELECT u.*, ur.role_id,
        CASE 
            WHEN s.user_id IS NOT NULL THEN 'Студент'
            WHEN t.user_id IS NOT NULL THEN 'Преподаватель'
            WHEN ur.role_id = 1 THEN 'Администратор'
            WHEN ur.role_id = 2 THEN 'Сотрудник деканата'
            ELSE 'Неизвестно'
        END as role_name,
        d.name as department_name,
        t.position as teacher_position,
        s.status as student_status,
        u.username as login
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN teachers t ON u.id = t.user_id
        LEFT JOIN departments d ON t.department_id = d.id
        ORDER BY u.last_name, u.first_name";

$result = $conn->query($sql);
if (!$result) {
    die("Ошибка запроса: " . $conn->error);
}
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление пользователями</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-plus-lg"></i> Добавить пользователя
            </button>
        </div>

        <!-- Таблица пользователей -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table id="usersTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Логин</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Дополнительно</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['last_name'] . ' ' . $user['first_name']; ?></td>
                                <td><?php echo $user['login']; ?></td>
                                <td><?php echo $user['role_name']; ?></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Активен</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['role_name'] == 'Преподаватель'): ?>
                                        Кафедра: <?php echo $user['department_name']; ?><br>
                                        Должность: <?php echo $user['teacher_position']; ?>
                                    <?php elseif ($user['role_name'] == 'Студент'): ?>
                                        Статус: <?php echo $user['student_status']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>)">
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

    <!-- Модальное окно создания пользователя -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createUserForm" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Фамилия</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" class="form-control" name="login" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Роль</label>
                            <select class="form-select" name="role_id" required onchange="toggleDepartmentField()">
                                <option value="1">Администратор</option>
                                <option value="2">Сотрудник деканата</option>
                                <option value="3">Преподаватель</option>
                                <option value="4">Студент</option>
                            </select>
                        </div>
                        <div class="mb-3 department-field" style="display: none;">
                            <label class="form-label">Кафедра</label>
                            <select class="form-select" name="department_id">
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3 department-field" style="display: none;">
                            <label class="form-label">Должность</label>
                            <input type="text" class="form-control" name="position">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="createUserForm" class="btn btn-primary">Создать</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования пользователя -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">Фамилия</label>
                            <input type="text" class="form-control" name="last_name" id="editLastName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" class="form-control" name="login" id="editLogin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Новый пароль (оставьте пустым, чтобы не менять)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Роль</label>
                            <select class="form-select" name="role_id" id="editRoleId" required>
                                <option value="1">Администратор</option>
                                <option value="2">Сотрудник деканата</option>
                                <option value="3">Преподаватель</option>
                                <option value="4">Студент</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="editIsActive">
                                <label class="form-check-label">Активен</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="editUserForm" class="btn btn-primary">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить этого пользователя?</p>
                    <form id="deleteUserForm" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="deleteUserId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="deleteUserForm" class="btn btn-danger">Удалить</button>
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
            $('#usersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
                }
            });
        });

        function toggleDepartmentField() {
            const roleSelect = document.querySelector('select[name="role_id"]');
            const departmentFields = document.querySelectorAll('.department-field');
            departmentFields.forEach(field => {
                field.style.display = roleSelect.value === '3' ? 'block' : 'none';
            });
        }

        function editUser(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editLastName').value = user.last_name;
            document.getElementById('editFirstName').value = user.first_name;
            document.getElementById('editLogin').value = user.login;
            document.getElementById('editRoleId').value = user.role_id;
            document.getElementById('editIsActive').checked = user.is_active === '1';
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        function deleteUser(userId) {
            document.getElementById('deleteUserId').value = userId;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
    </script>
</body>
</html> 