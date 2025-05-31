<?php
require_once 'config/config.php';
requireLogin();

// Получаем текущие настройки пользователя
$sql = "SELECT * FROM user_settings WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$settings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Преобразуем настройки в ассоциативный массив
$userSettings = [];
foreach ($settings as $setting) {
    $userSettings[$setting['setting_key']] = $setting['setting_value'];
}

// Обработка обновления настроек
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                // Обновляем настройки
                foreach ($_POST['settings'] as $key => $value) {
                    $sql = "INSERT INTO user_settings (user_id, setting_key, setting_value) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", $_SESSION['user_id'], $key, $value, $value);
                    $stmt->execute();
                }
                $_SESSION['success'] = "Настройки успешно обновлены";
                break;

            case 'change_password':
                if (password_verify($_POST['current_password'], $_SESSION['password'])) {
                    if ($_POST['new_password'] === $_POST['confirm_password']) {
                        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "Пароль успешно изменен";
                        } else {
                            $_SESSION['error'] = "Ошибка при изменении пароля";
                        }
                    } else {
                        $_SESSION['error'] = "Новые пароли не совпадают";
                    }
                } else {
                    $_SESSION['error'] = "Неверный текущий пароль";
                }
                break;
        }
        header("Location: settings.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="nav flex-column nav-pills" role="tablist">
                            <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#general">
                                <i class="bi bi-gear me-2"></i>Общие
                            </button>
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#notifications">
                                <i class="bi bi-bell me-2"></i>Уведомления
                            </button>
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#security">
                                <i class="bi bi-shield-lock me-2"></i>Безопасность
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Общие настройки -->
                    <div class="tab-pane fade show active" id="general">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Общие настройки</h5>
                                <hr>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    <div class="mb-3">
                                        <label class="form-label">Язык интерфейса</label>
                                        <select class="form-select" name="settings[language]">
                                            <option value="ru" <?php echo ($userSettings['language'] ?? '') == 'ru' ? 'selected' : ''; ?>>Русский</option>
                                            <option value="en" <?php echo ($userSettings['language'] ?? '') == 'en' ? 'selected' : ''; ?>>English</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Тема оформления</label>
                                        <select class="form-select" name="settings[theme]">
                                            <option value="light" <?php echo ($userSettings['theme'] ?? '') == 'light' ? 'selected' : ''; ?>>Светлая</option>
                                            <option value="dark" <?php echo ($userSettings['theme'] ?? '') == 'dark' ? 'selected' : ''; ?>>Темная</option>
                                        </select>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки уведомлений -->
                    <div class="tab-pane fade" id="notifications">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Настройки уведомлений</h5>
                                <hr>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="settings[notification_email]" 
                                                   <?php echo ($userSettings['notification_email'] ?? '') == 'true' ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Email-уведомления</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="settings[notification_telegram]"
                                                   <?php echo ($userSettings['notification_telegram'] ?? '') == 'true' ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Telegram-уведомления</label>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки безопасности -->
                    <div class="tab-pane fade" id="security">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Изменение пароля</h5>
                                <hr>
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label">Текущий пароль</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Новый пароль</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Подтверждение нового пароля</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Изменить пароль</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 