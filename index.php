<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Если пользователь уже авторизован, перенаправляем на dashboard
if (isLoggedIn()) {
            header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .feature-card {
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
        }
        .statistics {
            background-color: #f8f9fa;
            padding: 60px 0;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 40px 0;
        }
    </style>
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Возможности</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">О системе</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Контакты</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-primary me-2">Войти</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Главный экран -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Виртуальный деканат</h1>
                    <p class="lead mb-4">Современная система управления учебным процессом для студентов и преподавателей</p>
                    <a href="login.php" class="btn btn-light btn-lg">Начать работу</a>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/hero-image.svg" alt="Виртуальный деканат" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Статистика -->
    <section class="statistics">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="stat-number">1000+</div>
                    <div class="stat-label">Студентов</div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Преподавателей</div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stat-number">100+</div>
                    <div class="stat-label">Курсов</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Возможности -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Возможности системы</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check feature-icon"></i>
                            <h5 class="card-title">Расписание занятий</h5>
                            <p class="card-text">Удобное расписание с возможностью синхронизации и уведомлениями об изменениях</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="card-body text-center">
                            <i class="bi bi-graph-up feature-icon"></i>
                            <h5 class="card-title">Успеваемость</h5>
                            <p class="card-text">Мониторинг успеваемости, оценок и посещаемости в реальном времени</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="card-body text-center">
                            <i class="bi bi-file-earmark-text feature-icon"></i>
                            <h5 class="card-title">Учебные материалы</h5>
                            <p class="card-text">Доступ к учебным материалам, заданиям и методическим пособиям</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- О системе -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="mb-4">О виртуальном деканате</h2>
                    <p class="lead">Наша система разработана для упрощения взаимодействия между студентами, преподавателями и администрацией учебного заведения.</p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Автоматизация учебного процесса</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Удобный доступ к информации</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Эффективная коммуникация</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Современный интерфейс</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about-image.svg" alt="О системе" class="img-fluid">
                </div>
            </div>
            </div>
        </section>

    <!-- Контакты -->
    <section id="contact" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Контакты</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    <i class="bi bi-envelope feature-icon"></i>
                                    <h5>Email</h5>
                                    <p>support@dekanat.ru</p>
                                </div>
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    <i class="bi bi-telephone feature-icon"></i>
                                    <h5>Телефон</h5>
                                    <p>+7 (495) 123-45-67</p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <i class="bi bi-geo-alt feature-icon"></i>
                                    <h5>Адрес</h5>
                                    <p>г. Москва, ул. Примерная, д. 1</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </section>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p>Система управления учебным процессом</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Все права защищены.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>