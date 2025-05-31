<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT u.*, r.role_id, r.role_type 
            FROM users u 
            LEFT JOIN user_roles r ON u.id = r.user_id 
            WHERE u.username = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
            
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
            
        if (password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_type'] = $user['role_type'];
                
                // Получаем название роли
                $role_query = "SELECT name FROM roles WHERE id = ?";
                $stmt = $conn->prepare($role_query);
                $stmt->bind_param("i", $user['role_id']);
                $stmt->execute();
                $role_result = $stmt->get_result();
                $role_data = $role_result->fetch_assoc();
                $_SESSION['role_name'] = $role_data['name'];

                // Обновляем время последнего входа
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();

                // Логируем вход
                $log_sql = "INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (?, 'login', ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $log_stmt->bind_param("iss", $user['id'], $ip, $user_agent);
                $log_stmt->execute();

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Ваша учетная запись неактивна. Пожалуйста, обратитесь к администратору.";
            }
        } else {
            // Увеличиваем счетчик неудачных попыток
            $failed_sql = "UPDATE users SET 
                          failed_attempts = failed_attempts + 1,
                          last_failed_attempt = NOW()
                          WHERE id = ?";
            $failed_stmt = $conn->prepare($failed_sql);
            $failed_stmt->bind_param("i", $user['id']);
            $failed_stmt->execute();
            
            $error = "Неверный логин или пароль";
        }
    } else {
        $error = "Неверный логин или пароль";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Вход в систему</h3>
            
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Логин</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Войти</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="text-decoration-none">Забыли пароль?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>