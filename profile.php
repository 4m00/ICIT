<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Проверка авторизации
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Получение ID пользователя из сессии
$userId = $_SESSION['user_id'];

try {
    // Подключение к базе данных
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Запрос для получения информации о пользователе
    $sql = "SELECT u.*, 
            r.name as role_name,
            COALESCE(d.name, '') as department_name,
            COALESCE(t.position, '') as position,
            COALESCE(t.academic_degree, '') as academic_degree,
            COALESCE(s.student_id_number, '') as student_id,
            COALESCE(g.name, '') as group_name
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            LEFT JOIN teachers t ON u.id = t.user_id
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN students s ON u.id = s.user_id
            LEFT JOIN student_groups sg ON s.id = sg.student_id
            LEFT JOIN `groups` g ON sg.group_id = g.id
            WHERE u.id = :user_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
    if (!$user) {
        throw new Exception('Пользователь не найден');
    }

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}

// Обработка формы обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        // Базовые параметры для всех пользователей
        $params = [
            'email' => $_POST['email'],
            'user_id' => $userId
        ];

        // SQL запрос зависит от роли пользователя
        if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN) {
            $sql = "UPDATE users SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email
                    WHERE id = :user_id";
            
            // Добавляем параметры для администратора
            $params['first_name'] = $_POST['first_name'];
            $params['last_name'] = $_POST['last_name'];
        } else {
            $sql = "UPDATE users SET email = :email WHERE id = :user_id";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Обновляем сессию только если администратор
        if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN) {
            $_SESSION['first_name'] = $_POST['first_name'];
            $_SESSION['last_name'] = $_POST['last_name'];
            }
        
        $_SESSION['success'] = 'Профиль успешно обновлен';
        header('Location: profile.php');
        exit;
        } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при обновлении профиля: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя - <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
        <div class="row">
            <!-- Карточка профиля -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="card-title mb-0">
                            <?php echo htmlspecialchars($user['last_name'] . ' ' . $user['first_name']); ?>
                        </h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['role_name']); ?></p>
                        <div class="d-grid">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil-square me-2"></i>Редактировать профиль
                            </button>
                        </div>
                        </div>
                    </div>
                </div>
                
            <!-- Информация о пользователе -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Информация о пользователе</h5>
                        <hr>
                        <!-- Основная информация -->
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Логин</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Email</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Роль</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['role_name']); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Дата регистрации</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">
                                    <?php echo $user['created_at'] ? date('d.m.Y H:i', strtotime($user['created_at'])) : 'Не указана'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($user['department_name']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Кафедра</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['department_name']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($user['position']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Должность</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['position']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($user['academic_degree']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Учёная степень</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['academic_degree']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($user['student_id']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Номер студента</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['student_id']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($user['group_name']): ?>
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <p class="mb-0">Группа</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['group_name']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования профиля -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать профиль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN): ?>
                            <div class="mb-3">
                                <label class="form-label">Имя</label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Фамилия</label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Имя</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                       disabled readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Фамилия</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                       disabled readonly>
                                <div class="form-text text-muted">
                                    Для изменения имени и фамилии обратитесь к администратору
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>