<?php
require_once 'config/config.php';
requireLogin();

// Проверка прав доступа
if ($_SESSION['role_id'] != ROLE_ADMIN && $_SESSION['role_id'] != ROLE_DEKANAT) {
    header('Location: dashboard.php');
    exit();
}

// Получаем список групп
$sql = "SELECT id, name FROM `groups` ORDER BY name";
$result = $conn->query($sql);
if (!$result) {
    die("Ошибка запроса групп: " . $conn->error);
}
$groups = $result->fetch_all(MYSQLI_ASSOC);

// Получаем список кафедр
$sql = "SELECT id, name FROM departments ORDER BY name";
$result = $conn->query($sql);
if (!$result) {
    die("Ошибка запроса кафедр: " . $conn->error);
}
$departments = $result->fetch_all(MYSQLI_ASSOC);

// Получаем текущий семестр и год
$current_month = date('n');
$current_year = date('Y');
$current_semester = $current_month >= 9 ? 1 : 2;
$academic_year = $current_month >= 9 ? $current_year : $current_year - 1;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-4">
        <h1 class="mb-4">Отчеты</h1>

        <div class="row">
            <!-- Успеваемость -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Успеваемость</h5>
                    </div>
                    <div class="card-body">
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="performance">
                            <div class="mb-3">
                                <label class="form-label">Группа</label>
                                <select class="form-select" name="group_id">
                                    <option value="">Все группы</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Семестр</label>
                                <select class="form-select" name="semester">
                                    <option value="1">1 семестр</option>
                                    <option value="2">2 семестр</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Учебный год</label>
                                <select class="form-select" name="academic_year">
                                    <?php 
                                    for ($i = 0; $i < 5; $i++) {
                                        $year = $academic_year - $i;
                                        $yearStr = $year . '-' . ($year + 1);
                                        echo "<option value='$yearStr'>$yearStr</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-file-earmark-text"></i> Сформировать отчет
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Посещаемость -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Посещаемость</h5>
                    </div>
                    <div class="card-body">
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="attendance">
                            <div class="mb-3">
                                <label class="form-label">Группа</label>
                                <select class="form-select" name="group_id">
                                    <option value="">Все группы</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Период</label>
                                <div class="row">
                                    <div class="col">
                                        <input type="date" class="form-control" name="start_date">
                                    </div>
                                    <div class="col">
                                        <input type="date" class="form-control" name="end_date">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-file-earmark-text"></i> Сформировать отчет
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Сводный отчет по кафедре -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Сводный отчет по кафедре</h5>
                    </div>
                    <div class="card-body">
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="department">
                            <div class="mb-3">
                                <label class="form-label">Кафедра</label>
                                <select class="form-select" name="department_id">
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Учебный год</label>
                                <select class="form-select" name="academic_year">
                                    <?php 
                                    for ($i = 0; $i < 5; $i++) {
                                        $year = $academic_year - $i;
                                        $yearStr = $year . '-' . ($year + 1);
                                        echo "<option value='$yearStr'>$yearStr</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info">
                                <i class="bi bi-file-earmark-text"></i> Сформировать отчет
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Статистика по студентам -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0">Статистика по студентам</h5>
                    </div>
                    <div class="card-body">
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="student_stats">
                            <div class="mb-3">
                                <label class="form-label">Тип отчета</label>
                                <select class="form-select" name="report_subtype">
                                    <option value="academic_performance">Академическая успеваемость</option>
                                    <option value="attendance_stats">Статистика посещаемости</option>
                                    <option value="scholarship">Стипендиальное обеспечение</option>
                                    <option value="academic_debt">Академические задолженности</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Группа</label>
                                <select class="form-select" name="group_id">
                                    <option value="">Все группы</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Семестр</label>
                                <select class="form-select" name="semester">
                                    <option value="1">1 семестр</option>
                                    <option value="2">2 семестр</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-file-earmark-text"></i> Сформировать отчет
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- История отчетов -->
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">История отчетов</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Тип отчета</th>
                                <th>Параметры</th>
                                <th>Создал</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2024-01-20 15:30</td>
                                <td>Успеваемость</td>
                                <td>Группа ИСТ-21-1, 1 семестр 2024-2025</td>
                                <td>Иванов И.И.</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Добавьте больше строк с историей отчетов -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html> 