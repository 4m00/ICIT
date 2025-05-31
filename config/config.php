<?php
// Настройки сессии (должны быть до session_start())
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    session_start();
}

// Настройки приложения
define('APP_NAME', 'Виртуальный деканат');
define('APP_VERSION', '1.0.0');

// Настройки путей
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// Настройки времени
date_default_timezone_set('Europe/Moscow');
setlocale(LC_TIME, 'ru_RU.UTF-8', 'rus_RUS.UTF-8', 'Russian_Russia.UTF-8');

// Константы ролей
define('ROLE_ADMIN', 1);
define('ROLE_DEKANAT', 2);
define('ROLE_TEACHER', 3);
define('ROLE_STUDENT', 4);

// Подключение базы данных
require_once 'database.php';

// Проверка подключения к базе данных
if ($conn->connect_error) {
    die('Ошибка подключения к базе данных: ' . $conn->connect_error);
}

// Устанавливаем кодировку
if (!$conn->set_charset("utf8")) {
    die('Ошибка при установке кодировки utf8');
}

// Подключаем вспомогательные функции
require_once BASE_PATH . '/helpers/auth_helper.php';
?> 