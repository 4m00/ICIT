<?php
/**
 * Проверяет, авторизован ли пользователь
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Требует авторизации для доступа к странице
 * @return void
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Проверяет, имеет ли пользователь указанную роль
 * @param int $required_role ID требуемой роли
 * @return bool
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['role_id'] == $required_role;
}

/**
 * Проверяет, имеет ли пользователь одну из указанных ролей
 * @param array $roles Массив ID ролей
 * @return bool
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    return in_array($_SESSION['role_id'], $roles);
}

/**
 * Получает информацию о текущем пользователе
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT u.*, r.role_id, r.role_type 
            FROM users u 
            LEFT JOIN user_roles r ON u.id = r.user_id 
            WHERE u.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Выход из системы
 * @return void
 */
function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>