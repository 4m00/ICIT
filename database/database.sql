SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `eczhvuq1_dek` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `eczhvuq1_dek`;

DROP TABLE IF EXISTS `academic_performance`;
CREATE TABLE IF NOT EXISTS `academic_performance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `course_id` int NOT NULL,
  `semester` int NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `attendance_rate` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `academic_performance` (`id`, `student_id`, `course_id`, `semester`, `academic_year`, `final_grade`, `attendance_rate`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 1, '2024-2025', '85.50', '92.30', '2025-01-15 10:00:00', '2025-05-31 00:07:41'),
(2, 4, 2, 1, '2024-2025', '90.00', '95.00', '2025-01-15 10:00:00', '2025-05-31 00:07:41'),
(3, 4, 3, 1, '2024-2025', '88.75', '89.50', '2025-01-15 10:00:00', '2025-05-31 00:07:41');

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `category` enum('academic','administrative','system') NOT NULL,
  `importance` enum('low','medium','high') NOT NULL DEFAULT 'low',
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `category`, `importance`, `status`, `created_at`) VALUES
(1, 4, 'view_course', 'course', 1, 'Просмотр курса \"Базы данных\"', '127.0.0.1', 'academic', 'low', 'completed', '2025-01-15 09:30:00'),
(2, 2, 'grade_assignment', 'assignment', 1, 'Оценка задания по курсу \"Базы данных\"', '127.0.0.1', 'academic', 'medium', 'completed', '2025-01-15 10:15:00'),
(3, 1, 'system_backup', 'system', NULL, 'Еженедельное резервное копирование', '127.0.0.1', 'system', 'high', 'completed', '2025-01-15 00:00:00');

DROP TABLE IF EXISTS `assignment_submissions`;
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `submission_text` text,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text,
  `graded_by` int DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `student_id`, `submission_text`, `file_path`, `submitted_at`, `grade`, `feedback`, `graded_by`, `graded_at`) VALUES
(1, 1, 4, 'Решение задачи по SQL запросам', '/submissions/hw1_sql.pdf', '2025-01-10 15:30:00', '90.00', 'Отличная работа! Хорошо структурированные запросы.', 2, '2025-01-11 10:00:00'),
(2, 2, 4, 'Практическая работа по Python', '/submissions/python_practice.zip', '2025-01-12 14:45:00', '85.00', 'Хорошая реализация, но можно улучшить обработку ошибок.', 2, '2025-01-13 11:30:00');

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `lesson_id` int NOT NULL,
  `student_id` int NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `date` date NOT NULL,
  `type` enum('lecture','practice','lab','exam') NOT NULL DEFAULT 'lecture',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `lesson_id` (`lesson_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `attendance_documents`;
CREATE TABLE IF NOT EXISTS `attendance_documents` (
  `id` int NOT NULL,
  `attendance_id` int NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `attendance_types`;
CREATE TABLE IF NOT EXISTS `attendance_types` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text,
  `is_excused` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `attendance_types` (`id`, `name`, `code`, `description`, `is_excused`, `created_at`) VALUES
(1, 'Присутствие', 'PRESENT', 'Студент присутствовал на занятии', 0, '2025-05-28 23:22:25'),
(2, 'Отсутствие по болезни', 'SICK', 'Студент отсутствовал по болезни', 1, '2025-05-28 23:22:25'),
(3, 'Опоздание', 'LATE', 'Студент опоздал на занятие', 0, '2025-05-28 23:22:25'),
(4, 'Уважительная причина', 'EXCUSED', 'Отсутствие по уважительной причине', 1, '2025-05-28 23:22:25'),
(5, 'Неуважительная причина', 'UNEXCUSED', 'Отсутствие без уважительной причины', 0, '2025-05-28 23:22:25');

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text,
  `teacher_id` int DEFAULT NULL,
  `credits` int NOT NULL,
  `semester` int NOT NULL,
  `status` enum('active','completed','planned') NOT NULL DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `max_students` int DEFAULT NULL,
  `current_students` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `department_id` int DEFAULT NULL,
  `hours` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `courses` (`id`, `name`, `code`, `description`, `teacher_id`, `credits`, `semester`, `status`, `start_date`, `end_date`, `max_students`, `current_students`, `created_at`, `department_id`, `hours`) VALUES
(1, 'Базы данных', 'DB101', 'Основы проектирования и разработки баз данных', 2, 4, 1, 'active', NULL, NULL, NULL, NULL, '2025-05-28 23:22:25', 1, 72),
(2, 'Программирование на Python', 'PY101', 'Основы программирования на Python', 2, 4, 1, 'active', NULL, NULL, NULL, NULL, '2025-05-28 23:22:25', 1, 72),
(3, 'Веб-разработка', 'WEB101', 'Основы веб-разработки', 3, 4, 1, 'active', NULL, NULL, NULL, NULL, '2025-05-28 23:22:25', 1, 72),
(4, 'Математический анализ', 'MATH201', 'Основы математического анализа', 3, 5, 1, 'active', NULL, NULL, NULL, NULL, '2025-05-28 23:22:25', 2, 90),
(5, 'Информационная безопасность', 'SEC301', 'Основы информационной безопасности', 2, 4, 2, 'active', NULL, NULL, NULL, NULL, '2025-05-28 23:22:25', 1, 72);

DROP TABLE IF EXISTS `course_assignments`;
CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `due_date` datetime NOT NULL,
  `max_points` int NOT NULL DEFAULT '100',
  `weight` decimal(5,2) DEFAULT '1.00',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `course_materials`;
CREATE TABLE IF NOT EXISTS `course_materials` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(255) DEFAULT NULL,
  `type` enum('lecture','practice','homework','additional') NOT NULL,
  `uploaded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `course_materials` (`id`, `course_id`, `title`, `description`, `file_path`, `type`, `uploaded_by`, `created_at`) VALUES
(1, 1, 'Введение в SQL', 'Основы SQL и реляционных баз данных', '/materials/db/intro_sql.pdf', 'lecture', 2, '2025-05-28 23:22:25'),
(2, 1, 'Практика SQL', 'Задания по SQL запросам', '/materials/db/sql_practice.pdf', 'practice', 2, '2025-05-28 23:22:25'),
(3, 2, 'Основы Python', 'Введение в программирование на Python', '/materials/python/basics.pdf', 'lecture', 2, '2025-05-28 23:22:25'),
(4, 2, 'Задачи Python', 'Практические задания по Python', '/materials/python/tasks.pdf', 'homework', 2, '2025-05-28 23:22:25');

DROP TABLE IF EXISTS `course_prerequisites`;
CREATE TABLE IF NOT EXISTS `course_prerequisites` (
  `course_id` int NOT NULL,
  `prerequisite_course_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `course_prerequisites` (`course_id`, `prerequisite_course_id`, `created_at`) VALUES
(2, 1, '2025-05-28 23:22:25'),
(3, 2, '2025-05-28 23:22:25');

DROP TABLE IF EXISTS `deadlines`;
CREATE TABLE IF NOT EXISTS `deadlines` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `due_date` datetime NOT NULL,
  `type` enum('assignment','exam','project','other') NOT NULL,
  `status` enum('upcoming','ongoing','completed','overdue') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `deadlines` (`id`, `course_id`, `title`, `description`, `due_date`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Контрольная работа по SQL', 'Практическое задание по созданию и оптимизации запросов', '2025-02-01 12:00:00', 'assignment', 'upcoming', '2025-01-15 10:00:00', '2025-05-31 00:07:41'),
(2, 2, 'Проект Python', 'Разработка веб-приложения на Python', '2025-02-15 23:59:59', 'project', 'upcoming', '2025-01-15 10:00:00', '2025-05-31 00:07:41'),
(3, 3, 'Финальный экзамен', 'Итоговый экзамен по веб-разработке', '2025-05-20 09:00:00', 'exam', 'upcoming', '2025-01-15 10:00:00', '2025-05-31 00:07:41');

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faculty_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(50) DEFAULT NULL,
  `description` text,
  `head_user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `head_id` int DEFAULT NULL,
  `code` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `head_user_id` (`head_user_id`),
  KEY `head_id` (`head_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `departments` (`id`, `faculty_id`, `name`, `short_name`, `description`, `head_user_id`, `created_at`, `updated_at`, `head_id`, `code`) VALUES
(1, 1, 'Кафедра информационных систем', 'КИС', 'Кафедра информационных систем и технологий. Подготовка специалистов в области разработки информационных систем, баз данных и веб-технологий.', NULL, '2025-05-28 23:22:25', '2025-05-31 00:07:41', 2, 'ISD'),
(2, 1, 'Кафедра прикладной математики', 'КПМ', 'Кафедра прикладной математики и компьютерного моделирования. Подготовка специалистов в области математического моделирования и вычислительной математики.', NULL, '2025-05-28 23:22:25', '2025-05-31 00:07:41', 3, 'APM');

DROP TABLE IF EXISTS `faculties`;
CREATE TABLE IF NOT EXISTS `faculties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(50) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `faculties` (`id`, `name`, `short_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Факультет информационных технологий', 'ФИТ', 'Подготовка специалистов в области информационных технологий, программирования и компьютерных наук', '2025-05-31 00:07:41', '2025-05-31 00:07:41'),
(2, 'Факультет технологии машиностроения', 'ФТМ', 'Подготовка специалистов в области машиностроения и автоматизации производства', '2025-05-31 00:07:41', '2025-05-31 00:07:41');

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `course_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `grade` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `grades` (`student_id`, `course_id`, `teacher_id`, `grade`, `comment`, `created_at`) VALUES
(1, 1, 2, 85, 'Хорошая работа на экзамене', '2025-12-20 14:30:00'),
(1, 2, 2, 90, 'Отличное выполнение практических заданий', '2025-12-21 15:00:00');

DROP TABLE IF EXISTS `grade_components`;
CREATE TABLE IF NOT EXISTS `grade_components` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `due_date` date DEFAULT NULL,
  `weight` decimal(3,2) DEFAULT '1.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `grade_components` (`id`, `course_id`, `type_id`, `name`, `description`, `due_date`, `weight`, `created_at`) VALUES
(1, 1, 1, 'Финальный экзамен', 'Итоговый экзамен по базам данных', '2025-12-20', '0.50', '2025-05-28 23:22:25'),
(2, 1, 2, 'Контрольная работа 1', 'SQL и проектирование БД', '2025-10-15', '0.20', '2025-05-28 23:22:25'),
(3, 2, 1, 'Финальный экзамен', 'Итоговый экзамен по Python', '2025-12-21', '0.50', '2025-05-28 23:22:25'),
(4, 2, 3, 'Лабораторная работа 1', 'Основы программирования на Python', '2025-09-30', '0.15', '2025-05-28 23:22:25');

DROP TABLE IF EXISTS `grade_types`;
CREATE TABLE IF NOT EXISTS `grade_types` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `max_score` int NOT NULL,
  `weight` decimal(3,2) DEFAULT '1.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `grade_types` (`id`, `name`, `description`, `max_score`, `weight`, `created_at`) VALUES
(1, 'Экзамен', 'Итоговый экзамен по курсу', 100, '0.50', '2025-05-28 23:22:25'),
(2, 'Контрольная работа', 'Промежуточная контрольная работа', 100, '0.20', '2025-05-28 23:22:25'),
(3, 'Лабораторная работа', 'Практическая лабораторная работа', 100, '0.15', '2025-05-28 23:22:25'),
(4, 'Домашнее задание', 'Индивидуальное домашнее задание', 100, '0.10', '2025-05-28 23:22:25'),
(5, 'Активность на занятиях', 'Участие в обсуждениях и работа на занятиях', 100, '0.05', '2025-05-28 23:22:25');

DROP TABLE IF EXISTS `groups`;
CREATE TABLE IF NOT EXISTS `groups` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `faculty` varchar(100) NOT NULL,
  `year_of_study` int NOT NULL,
  `curator_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `department_id` int DEFAULT NULL,
  `code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `groups` (`id`, `name`, `faculty`, `year_of_study`, `curator_id`, `created_at`, `updated_at`, `department_id`, `code`) VALUES
(1, 'ПМ-21-1', 'ФИТ', 2, 3, '2025-05-28 23:22:25', '2025-05-31 00:07:41', 2, '');

DROP TABLE IF EXISTS `lessons`;
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `group_id` int NOT NULL,
  `type` enum('lecture','practice','consultation','exam') NOT NULL,
  `topic` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `lessons` (`id`, `course_id`, `teacher_id`, `group_id`, `type`, `topic`, `date`, `start_time`, `end_time`, `room`, `created_at`) VALUES
(1, 1, 2, 1, 'lecture', 'Введение в базы данных', '2025-01-15', '09:00:00', '10:30:00', '301', '2025-01-01 10:00:00'),
(2, 2, 2, 1, 'practice', 'Основы Python', '2025-01-15', '10:45:00', '12:15:00', '302', '2025-01-01 10:00:00'),
(3, 3, 3, 1, 'lecture', 'HTML и CSS', '2025-01-16', '13:00:00', '14:30:00', '303', '2025-01-01 10:00:00');

-- Добавляем больше занятий в таблицу lessons
INSERT INTO lessons (id, course_id, teacher_id, group_id, type, topic, date, start_time, end_time, room) VALUES
-- Понедельник
(4, 1, 2, 1, 'lecture', 'Нормализация баз данных', '2025-01-15', '09:00:00', '10:30:00', '301'),
(5, 2, 2, 1, 'practice', 'Работа с коллекциями в Python', '2025-01-15', '10:45:00', '12:15:00', '302'),
(6, 3, 3, 1, 'lab', 'Разработка веб-интерфейса', '2025-01-15', '13:00:00', '14:30:00', '303'),

-- Вторник
(7, 4, 3, 1, 'lecture', 'Интегральное исчисление', '2025-01-16', '09:00:00', '10:30:00', '304'),
(8, 4, 3, 1, 'practice', 'Решение интегралов', '2025-01-16', '10:45:00', '12:15:00', '304'),
(9, 5, 2, 1, 'lab', 'Анализ защищенности сети', '2025-01-16', '13:00:00', '14:30:00', '305'),

-- Среда
(10, 2, 2, 1, 'lecture', 'ООП в Python', '2025-01-17', '09:00:00', '10:30:00', '302'),
(11, 2, 2, 1, 'practice', 'Создание классов', '2025-01-17', '10:45:00', '12:15:00', '302'),
(12, 3, 3, 1, 'lab', 'JavaScript и DOM', '2025-01-17', '13:00:00', '14:30:00', '303'),

-- Четверг
(13, 3, 3, 1, 'lecture', 'Фреймворки JavaScript', '2025-01-18', '09:00:00', '10:30:00', '303'),
(14, 3, 3, 1, 'practice', 'Работа с React', '2025-01-18', '10:45:00', '12:15:00', '303'),
(15, 4, 3, 1, 'lab', 'Численные методы', '2025-01-18', '13:00:00', '14:30:00', '304'),

-- Пятница
(16, 5, 2, 1, 'lecture', 'Криптография', '2025-01-19', '09:00:00', '10:30:00', '305'),
(17, 4, 3, 1, 'practice', 'Дифференциальные уравнения', '2025-01-19', '10:45:00', '12:15:00', '304'),
(18, 1, 2, 1, 'lab', 'Оптимизация SQL запросов', '2025-01-19', '13:00:00', '14:30:00', '301');

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','error','success') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`, `read_at`) VALUES
(1, 4, 'Новая оценка', 'Вы получили оценку 90 за задание по SQL', 'success', 1, '2025-01-11 10:00:00', '2025-01-11 10:30:00'),
(2, 4, 'Предстоящий дедлайн', 'Напоминание: сдача проекта по Python через 3 дня', 'warning', 0, '2025-01-12 09:00:00', NULL),
(3, 2, 'Система', 'Резервное копирование успешно завершено', 'info', 1, '2025-01-15 00:05:00', '2025-01-15 08:00:00');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'Администратор системы', '2025-05-28 20:43:27'),
(2, 'dekanat', 'Сотрудник деканата', '2025-05-28 20:43:27'),
(3, 'teacher', 'Преподаватель', '2025-05-28 20:43:27'),
(4, 'student', 'Студент', '2025-05-28 20:43:27');

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int NOT NULL,
  `course_id` int DEFAULT NULL,
  `group_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `day_of_week` int NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `type` enum('lecture','practice','lab') NOT NULL,
  `semester` int NOT NULL,
  `year` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_modified_by` int DEFAULT NULL,
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `schedule` (`id`, `course_id`, `group_id`, `teacher_id`, `room`, `day_of_week`, `start_time`, `end_time`, `type`, `semester`, `year`, `is_active`, `last_modified_by`, `last_modified_at`, `created_at`) VALUES
(1, 1, 1, 2, '301', 1, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1, 1, '2025-05-31 00:07:41', '2025-01-01 10:00:00'),
(2, 2, 1, 2, '302', 1, '10:45:00', '12:15:00', 'practice', 1, 2025, 1, 1, '2025-05-31 00:07:41', '2025-01-01 10:00:00'),
(3, 3, 1, 3, '303', 2, '13:00:00', '14:30:00', 'lecture', 1, 2025, 1, 1, '2025-05-31 00:07:41', '2025-01-01 10:00:00');

DROP TABLE IF EXISTS `schedule_changes`;
CREATE TABLE IF NOT EXISTS `schedule_changes` (
  `id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `change_type` enum('cancellation','replacement','room_change','time_change') NOT NULL,
  `new_teacher_id` int DEFAULT NULL,
  `new_room` varchar(50) DEFAULT NULL,
  `new_start_time` time DEFAULT NULL,
  `new_end_time` time DEFAULT NULL,
  `change_date` date NOT NULL,
  `reason` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `schedule_changes` (`id`, `schedule_id`, `change_type`, `new_teacher_id`, `new_room`, `new_start_time`, `new_end_time`, `change_date`, `reason`, `created_by`, `created_at`) VALUES
(1, 1, 'room_change', NULL, '305', NULL, NULL, '2025-01-22', 'Технические работы в аудитории 301', 1, '2025-01-15 10:00:00'),
(2, 2, 'time_change', NULL, NULL, '11:00:00', '12:30:00', '2025-01-22', 'Перенос занятия по просьбе преподавателя', 1, '2025-01-15 10:00:00');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` text,
  `last_activity` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `student_id_number` varchar(20) NOT NULL,
  `enrollment_date` date NOT NULL,
  `current_semester` int DEFAULT '1',
  `status` enum('active','inactive','graduated','academic_leave') NOT NULL DEFAULT 'active',
  `faculty_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id_number` (`student_id_number`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `students` (`id`, `user_id`, `student_id_number`, `enrollment_date`, `current_semester`, `status`, `faculty_id`, `created_at`, `updated_at`) VALUES
(1, 4, 'ST621237', '2025-09-01', 1, 'active', 1, '2025-05-28 23:22:25', '2025-05-31 00:07:41');

DROP TABLE IF EXISTS `student_course_enrollment`;
CREATE TABLE IF NOT EXISTS `student_course_enrollment` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `course_id` int NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('enrolled','completed','dropped','failed') NOT NULL DEFAULT 'enrolled',
  `final_grade` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `student_groups`;
CREATE TABLE IF NOT EXISTS `student_groups` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `group_id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `status` enum('active','inactive','graduated') NOT NULL DEFAULT 'active',
  `joined_date` date NOT NULL,
  `left_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `student_groups` (`id`, `name`, `student_id`, `group_id`, `department_id`, `status`, `joined_date`, `left_date`, `created_at`, `updated_at`) VALUES
(1, 'ИСТ-21-1', 4, 1, NULL, 'active', '2025-09-01', NULL, '2025-05-28 23:22:25', '2025-05-31 00:07:41');

DROP TABLE IF EXISTS `student_group_members`;
CREATE TABLE IF NOT EXISTS `student_group_members` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `group_id` int NOT NULL,
  `status` enum('active','inactive','graduated') NOT NULL DEFAULT 'active',
  `joined_date` date NOT NULL,
  `left_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `student_group_members` (`id`, `student_id`, `group_id`, `status`, `joined_date`, `left_date`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'active', '2025-09-01', NULL, '2025-05-28 23:22:25', '2025-05-31 00:07:41');

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `department` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL,
  `academic_degree` varchar(50) DEFAULT NULL,
  `employment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `department_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `teachers` (`id`, `user_id`, `department`, `position`, `academic_degree`, `employment_date`, `created_at`, `department_id`) VALUES
(1, 2, 'Кафедра информационных систем', 'Профессор', 'Доктор технических наук', '2020-09-01', '2025-05-28 23:22:25', 1),
(2, 3, 'Кафедра прикладной математики', 'Доцент', 'Кандидат технических наук', '2021-09-01', '2025-05-28 23:22:25', 2);

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `failed_attempts` int DEFAULT '0',
  `last_failed_attempt` timestamp NULL DEFAULT NULL,
  `reset_code` varchar(32) DEFAULT NULL,
  `reset_code_expires` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `created_at`, `last_login`, `is_active`, `failed_attempts`, `last_failed_attempt`, `reset_code`, `reset_code_expires`) VALUES
(1, 'admin', '$2y$10$n9KX49UU1e0qnAAtjhJ5heq006c.N/Me2PSPibukV7rPZCsfrRBRO', 'admin@example.com', 'Админ', 'Системный', '2025-05-28 20:43:27', '2025-05-31 00:09:02', 1, 1, '2025-05-31 00:08:55', 'b433d98a18c8d63917fc51d6091f9ad5', '2025-05-28 21:47:52'),
(2, 'tc121212', '$2y$10$Jy1Mak8QkUssRHOeXdnRtuYibs0VLbbS9tlHpDyEbKn4eJ2r8quwe', 'ser.bych@stamkin.ru', 'Сергей', 'Бычков', '2025-05-28 20:59:40', NULL, 1, 0, NULL, NULL, NULL),
(3, 'tc123456', '$2y$10$ozAnGUMKqZAe5X8NAJQ87uiUzf7k9l7KGxZ3u3mmkBPuqsxW5jZrO', 'nat.bych@stamkin.ru', 'Наталья', 'Бычкова', '2025-05-28 21:00:13', NULL, 1, 0, NULL, NULL, NULL),
(4, 'st621237', '$2y$10$6YmBGjEBfHcqob/WqxIiBevi5bUWfE7beJwzcEagZhyDatc..Ku92', 'art.sabo@stankin.ru', 'Артём', 'Сабо', '2025-05-28 21:00:43', '2025-05-31 00:10:20', 1, 1, '2025-05-31 00:10:13', NULL, NULL),
(5, 'dek123', '$2y$10$nmbUchu6Tdqrf.FJ/quIWOKqzbzkDFUU5bXbqEiEHr/x2Pwpw2KT2', 'dek123@stankin.ru', 'Вадим', 'Носовицкий', '2025-05-31 00:09:44', NULL, 1, 0, NULL, NULL, NULL);

DROP TABLE IF EXISTS `user_logs`;
CREATE TABLE IF NOT EXISTS `user_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `user_logs` (`id`, `user_id`, `action`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 4, 'login', '127.0.0.1', 'Mozilla/5.0', '2025-05-28 23:22:25'),
(2, 4, 'view_schedule', '127.0.0.1', 'Mozilla/5.0', '2025-05-28 23:22:25'),
(3, 2, 'grade_submission', '127.0.0.1', 'Mozilla/5.0', '2025-05-28 23:22:25');

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `role_type` enum('primary','secondary') NOT NULL DEFAULT 'primary',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `user_roles` (`user_id`, `role_id`, `role_type`, `created_at`) VALUES
(1, 1, 'primary', '2025-05-28 20:43:27'),
(2, 3, 'primary', '2025-05-28 20:59:40'),
(3, 3, 'primary', '2025-05-28 21:00:13'),
(4, 4, 'primary', '2025-05-28 21:00:43'),
(5, 2, 'primary', '2025-05-31 00:09:44');

DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE IF NOT EXISTS `user_settings` (
  `user_id` int NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `user_settings` (`user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(4, 'language', 'ru', '2025-05-28 23:22:25', '2025-05-31 00:07:41'),
(4, 'notification_email', 'true', '2025-05-28 23:22:25', '2025-05-31 00:07:41'),
(4, 'notification_telegram', 'true', '2025-05-28 23:22:25', '2025-05-31 00:07:41'),
(4, 'theme', 'light', '2025-05-28 23:22:25', '2025-05-31 00:07:41');

-- Добавляем больше студентов
INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `created_at`, `is_active`) VALUES
(6, 'st621238', '$2y$10$6YmBGjEBfHcqob/WqxIiBevi5bUWfE7beJwzcEagZhyDatc..Ku92', 'ivan.petrov@stankin.ru', 'Иван', 'Петров', '2025-05-28 21:00:43', 1),
(7, 'st621239', '$2y$10$6YmBGjEBfHcqob/WqxIiBevi5bUWfE7beJwzcEagZhyDatc..Ku92', 'elena.ivanova@stankin.ru', 'Елена', 'Иванова', '2025-05-28 21:00:43', 1),
(8, 'st621240', '$2y$10$6YmBGjEBfHcqob/WqxIiBevi5bUWfE7beJwzcEagZhyDatc..Ku92', 'pavel.smirnov@stankin.ru', 'Павел', 'Смирнов', '2025-05-28 21:00:43', 1),
(9, 'st621241', '$2y$10$6YmBGjEBfHcqob/WqxIiBevi5bUWfE7beJwzcEagZhyDatc..Ku92', 'maria.kozlova@stankin.ru', 'Мария', 'Козлова', '2025-05-28 21:00:43', 1),
(10, 'st621242', '$2y$10$6YmBGjEBfHcqob/WqxIiBevi5bUWfE7beJwzcEagZhyDatc..Ku92', 'dmitry.sokolov@stankin.ru', 'Дмитрий', 'Соколов', '2025-05-28 21:00:43', 1);

INSERT INTO `user_roles` (`user_id`, `role_id`, `role_type`) VALUES
(6, 4, 'primary'),
(7, 4, 'primary'),
(8, 4, 'primary'),
(9, 4, 'primary'),
(10, 4, 'primary');

INSERT INTO `students` (`id`, `user_id`, `student_id_number`, `enrollment_date`, `current_semester`, `status`, `faculty_id`) VALUES
(2, 6, 'ST621238', '2025-09-01', 1, 'active', 1),
(3, 7, 'ST621239', '2025-09-01', 1, 'active', 1),
(4, 8, 'ST621240', '2025-09-01', 1, 'active', 1),
(5, 9, 'ST621241', '2025-09-01', 1, 'active', 1),
(6, 10, 'ST621242', '2025-09-01', 1, 'active', 1);

-- Добавляем больше групп
INSERT INTO `groups` (`id`, `name`, `faculty`, `year_of_study`, `curator_id`, `department_id`, `code`) VALUES
(2, 'ИСТ-21-2', 'ФИТ', 2, 2, 1, 'IST212'),
(3, 'ПМ-21-2', 'ФИТ', 2, 3, 2, 'PM212'),
(4, 'ИСТ-22-1', 'ФИТ', 1, 2, 1, 'IST221'),
(5, 'ПМ-22-1', 'ФИТ', 1, 3, 2, 'PM221');

-- Распределяем студентов по группам
INSERT INTO `student_groups` (`id`, `name`, `student_id`, `group_id`, `status`, `joined_date`) VALUES
(2, 'ИСТ-21-2', 2, 2, 'active', '2025-09-01'),
(3, 'ИСТ-21-2', 3, 2, 'active', '2025-09-01'),
(4, 'ПМ-21-2', 4, 3, 'active', '2025-09-01'),
(5, 'ПМ-21-2', 5, 3, 'active', '2025-09-01'),
(6, 'ИСТ-22-1', 6, 4, 'active', '2025-09-01');

-- Добавляем полное расписание для всех групп
INSERT INTO `schedule` (`id`, `course_id`, `group_id`, `teacher_id`, `room`, `day_of_week`, `start_time`, `end_time`, `type`, `semester`, `year`, `is_active`) VALUES
-- Понедельник
(11, 1, 1, 2, '301', 1, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),
(12, 1, 1, 2, '301', 1, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),
(13, 2, 1, 2, '302', 1, '13:00:00', '14:30:00', 'lab', 1, 2025, 1),
(14, 3, 1, 3, '303', 1, '14:45:00', '16:15:00', 'lecture', 1, 2025, 1),
-- Вторник
(15, 4, 1, 3, '304', 2, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),
(16, 4, 1, 3, '304', 2, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),
(17, 5, 1, 2, '305', 2, '13:00:00', '14:30:00', 'lab', 1, 2025, 1),
(18, 5, 1, 2, '305', 2, '14:45:00', '16:15:00', 'practice', 1, 2025, 1),
-- Среда
(19, 2, 1, 2, '302', 3, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),
(20, 2, 1, 2, '302', 3, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),
(21, 3, 1, 3, '303', 3, '13:00:00', '14:30:00', 'lab', 1, 2025, 1),
(22, 1, 1, 2, '301', 3, '14:45:00', '16:15:00', 'practice', 1, 2025, 1),
-- Четверг
(23, 3, 1, 3, '303', 4, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),
(24, 3, 1, 3, '303', 4, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),
(25, 4, 1, 3, '304', 4, '13:00:00', '14:30:00', 'lab', 1, 2025, 1),
(26, 5, 1, 2, '305', 4, '14:45:00', '16:15:00', 'lecture', 1, 2025, 1),
-- Пятница
(27, 5, 1, 2, '305', 5, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),
(28, 4, 1, 3, '304', 5, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),
(29, 1, 1, 2, '301', 5, '13:00:00', '14:30:00', 'lab', 1, 2025, 1),
(30, 2, 1, 2, '302', 5, '14:45:00', '16:15:00', 'practice', 1, 2025, 1);

-- Добавляем все оценки
INSERT INTO `grades` (`student_id`, `course_id`, `teacher_id`, `grade`, `comment`, `created_at`) VALUES
(1, 1, 2, 92, 'Отличное понимание SQL и проектирования баз данных', '2025-12-20 14:30:00'),
(1, 2, 2, 88, 'Хорошая работа с Python, особенно в ООП', '2025-12-21 15:00:00'),
(1, 3, 3, 95, 'Превосходная разработка веб-приложения', '2025-12-22 11:30:00'),
(1, 4, 3, 85, 'Хорошее решение математических задач', '2025-12-23 10:00:00'),
(1, 5, 2, 90, 'Отличное понимание принципов безопасности', '2025-12-24 12:00:00');

-- Добавляем посещаемость для каждого занятия
INSERT INTO `attendance` (`id`, `lesson_id`, `student_id`, `status`, `date`, `type`, `created_at`) VALUES
-- Понедельник (15 января)
(13, 11, 4, 'present', '2025-01-15', 'lecture', '2025-01-15 09:00:00'),
(14, 12, 4, 'present', '2025-01-15', 'practice', '2025-01-15 10:45:00'),
(15, 13, 4, 'present', '2025-01-15', 'lab', '2025-01-15 13:00:00'),
(16, 14, 4, 'present', '2025-01-15', 'lecture', '2025-01-15 14:45:00'),
-- Вторник (16 января)
(17, 15, 4, 'present', '2025-01-16', 'lecture', '2025-01-16 09:00:00'),
(18, 16, 4, 'late', '2025-01-16', 'practice', '2025-01-16 10:45:00'),
(19, 17, 4, 'present', '2025-01-16', 'lab', '2025-01-16 13:00:00'),
(20, 18, 4, 'present', '2025-01-16', 'practice', '2025-01-16 14:45:00'),
-- Среда (17 января)
(21, 19, 4, 'present', '2025-01-17', 'lecture', '2025-01-17 09:00:00'),
(22, 20, 4, 'present', '2025-01-17', 'practice', '2025-01-17 10:45:00'),
(23, 21, 4, 'late', '2025-01-17', 'lab', '2025-01-17 13:00:00'),
(24, 22, 4, 'present', '2025-01-17', 'practice', '2025-01-17 14:45:00'),
-- Четверг (18 января)
(25, 23, 4, 'present', '2025-01-18', 'lecture', '2025-01-18 09:00:00'),
(26, 24, 4, 'present', '2025-01-18', 'practice', '2025-01-18 10:45:00'),
(27, 25, 4, 'absent', '2025-01-18', 'lab', '2025-01-18 13:00:00'),
(28, 26, 4, 'present', '2025-01-18', 'lecture', '2025-01-18 14:45:00'),
-- Пятница (19 января)
(29, 27, 4, 'present', '2025-01-19', 'lecture', '2025-01-19 09:00:00'),
(30, 28, 4, 'present', '2025-01-19', 'practice', '2025-01-19 10:45:00'),
(31, 29, 4, 'present', '2025-01-19', 'lab', '2025-01-19 13:00:00'),
(32, 30, 4, 'present', '2025-01-19', 'practice', '2025-01-19 14:45:00');

-- Добавляем все сданные работы
INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `student_id`, `submission_text`, `file_path`, `submitted_at`, `grade`, `feedback`, `graded_by`, `graded_at`) VALUES
(7, 1, 4, 'Разработка системы управления библиотекой', '/submissions/artem_sabo/library_db.pdf', '2025-01-10 15:30:00', '92.00', 'Отличная архитектура базы данных, хорошая нормализация', 2, '2025-01-11 10:00:00'),
(8, 2, 4, 'Разработка REST API на Python', '/submissions/artem_sabo/rest_api.zip', '2025-01-12 14:00:00', '88.00', 'Хорошая структура кода, есть документация', 2, '2025-01-13 11:00:00'),
(9, 3, 4, 'Веб-приложение для управления задачами', '/submissions/artem_sabo/task_manager.zip', '2025-01-14 16:00:00', '95.00', 'Отличный дизайн и функциональность', 3, '2025-01-15 12:00:00'),
(10, 4, 4, 'Решение задач по интегрированию', '/submissions/artem_sabo/calculus_hw.pdf', '2025-01-16 18:00:00', '85.00', 'Хорошие решения, есть небольшие неточности', 3, '2025-01-17 10:00:00');

-- Добавляем уведомления для Артёма
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`, `read_at`) VALUES
(14, 4, 'Высокая оценка', 'Поздравляем! Вы получили 95 баллов за проект по веб-разработке', 'success', 1, '2025-01-15 12:05:00', '2025-01-15 12:10:00'),
(15, 4, 'Новый материал', 'Добавлены новые материалы по курсу "Базы данных"', 'info', 0, '2025-01-16 09:00:00', NULL),
(16, 4, 'Напоминание', 'Завтра контрольная работа по Python', 'warning', 1, '2025-01-16 15:00:00', '2025-01-16 15:05:00'),
(17, 4, 'Пропуск занятия', 'Зафиксировано отсутствие на лабораторной работе 18.01', 'error', 0, '2025-01-18 14:00:00', NULL),
(18, 4, 'Консультация', 'Назначена консультация по Математическому анализу', 'info', 0, '2025-01-19 10:00:00', NULL);

-- Добавляем записи в academic_performance
INSERT INTO `academic_performance` (`id`, `student_id`, `course_id`, `semester`, `academic_year`, `final_grade`, `attendance_rate`) VALUES
(11, 4, 1, 1, '2024-2025', '92.00', '95.00'),
(12, 4, 2, 1, '2024-2025', '88.00', '92.50'),
(13, 4, 3, 1, '2024-2025', '95.00', '97.00'),
(14, 4, 4, 1, '2024-2025', '85.00', '88.00'),
(15, 4, 5, 1, '2024-2025', '90.00', '94.00');

-- Добавляем логи активности
INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `category`, `importance`, `status`) VALUES
(11, 4, 'view_course', 'course', 1, 'Просмотр материалов по БД', '127.0.0.1', 'academic', 'low', 'completed'),
(12, 4, 'submit_assignment', 'assignment', 1, 'Сдача проекта по БД', '127.0.0.1', 'academic', 'medium', 'completed'),
(13, 4, 'take_exam', 'exam', 2, 'Сдача экзамена по Python', '127.0.0.1', 'academic', 'high', 'completed'),
(14, 4, 'download_material', 'material', 5, 'Загрузка лекции по SQL', '127.0.0.1', 'academic', 'low', 'completed'),
(15, 4, 'view_grades', 'grades', NULL, 'Просмотр оценок', '127.0.0.1', 'academic', 'low', 'completed');

-- Добавляем настройки пользователя
INSERT INTO `user_settings` (`user_id`, `setting_key`, `setting_value`) VALUES
(4, 'dashboard_layout', 'compact'),
(4, 'notification_sound', 'enabled'),
(4, 'notification_browser', 'enabled'),
(4, 'color_scheme', 'system'),
(4, 'font_size', 'medium');

-- Обновляем группу для Артёма Сабо
UPDATE `student_groups` SET 
  `name` = 'ИСТ-21-1',
  `student_id` = 4,
  `group_id` = 1,
  `department_id` = 1,
  `status` = 'active',
  `joined_date` = '2024-09-01'
WHERE `student_id` = 4;

-- Обновляем оценки для Артёма Сабо
DELETE FROM `grades` WHERE `student_id` = 4;
INSERT INTO `grades` (`student_id`, `course_id`, `teacher_id`, `grade`, `comment`, `created_at`) VALUES
-- Базы данных
(4, 1, 2, 95, 'Отличное понимание SQL и проектирования баз данных. Успешно выполнил все практические задания', '2024-10-15 14:30:00'),
(4, 1, 2, 92, 'Хорошая работа с триггерами и хранимыми процедурами', '2024-11-20 15:00:00'),
(4, 1, 2, 98, 'Превосходный проект семестровой работы - система управления библиотекой', '2024-12-25 16:00:00'),
-- Python
(4, 2, 2, 88, 'Хорошее понимание ООП в Python', '2024-10-10 11:30:00'),
(4, 2, 2, 94, 'Отличная реализация алгоритмов сортировки и поиска', '2024-11-15 12:00:00'),
(4, 2, 2, 91, 'Качественная разработка REST API на Flask', '2024-12-20 13:30:00'),
-- Веб-разработка
(4, 3, 3, 96, 'Отличное владение HTML5 и CSS3', '2024-10-05 10:00:00'),
(4, 3, 3, 93, 'Хорошая работа с JavaScript и DOM', '2024-11-10 11:00:00'),
(4, 3, 3, 97, 'Превосходный проект на React', '2024-12-15 12:00:00'),
-- Математический анализ
(4, 4, 3, 85, 'Хорошее решение задач по дифференцированию', '2024-10-20 09:30:00'),
(4, 4, 3, 88, 'Улучшение в понимании интегралов', '2024-11-25 10:30:00'),
(4, 4, 3, 90, 'Хорошая работа с рядами и последовательностями', '2024-12-30 11:30:00'),
-- Информационная безопасность
(4, 5, 2, 94, 'Отличное понимание основ криптографии', '2024-10-25 13:00:00'),
(4, 5, 2, 92, 'Хорошая работа по анализу защищенности сети', '2024-11-30 14:00:00'),
(4, 5, 2, 96, 'Отличный проект по безопасности веб-приложений', '2024-12-28 15:00:00');

-- Обновляем посещаемость для Артёма Сабо (более детальная статистика за семестр)
DELETE FROM `attendance` WHERE `student_id` = 4;
INSERT INTO `attendance` (`lesson_id`, `student_id`, `status`, `date`, `type`, `created_at`) VALUES
-- Сентябрь (первые две недели)
(11, 4, 'present', '2024-09-02', 'lecture', '2024-09-02 09:00:00'),
(12, 4, 'present', '2024-09-02', 'practice', '2024-09-02 10:45:00'),
(13, 4, 'present', '2024-09-02', 'lab', '2024-09-02 13:00:00'),
(14, 4, 'present', '2024-09-02', 'lecture', '2024-09-02 14:45:00'),

(15, 4, 'present', '2024-09-03', 'lecture', '2024-09-03 09:00:00'),
(16, 4, 'late', '2024-09-03', 'practice', '2024-09-03 10:45:00'),
(17, 4, 'present', '2024-09-03', 'lab', '2024-09-03 13:00:00'),
(18, 4, 'present', '2024-09-03', 'practice', '2024-09-03 14:45:00'),

(19, 4, 'present', '2024-09-04', 'lecture', '2024-09-04 09:00:00'),
(20, 4, 'present', '2024-09-04', 'practice', '2024-09-04 10:45:00'),
(21, 4, 'present', '2024-09-04', 'lab', '2024-09-04 13:00:00'),
(22, 4, 'present', '2024-09-04', 'practice', '2024-09-04 14:45:00'),

-- Октябрь (выборочно)
(23, 4, 'present', '2024-10-07', 'lecture', '2024-10-07 09:00:00'),
(24, 4, 'present', '2024-10-07', 'practice', '2024-10-07 10:45:00'),
(25, 4, 'absent', '2024-10-07', 'lab', '2024-10-07 13:00:00'),
(26, 4, 'present', '2024-10-07', 'lecture', '2024-10-07 14:45:00'),

-- Ноябрь (выборочно)
(27, 4, 'present', '2024-11-11', 'lecture', '2024-11-11 09:00:00'),
(28, 4, 'late', '2024-11-11', 'practice', '2024-11-11 10:45:00'),
(29, 4, 'present', '2024-11-11', 'lab', '2024-11-11 13:00:00'),
(30, 4, 'present', '2024-11-11', 'practice', '2024-11-11 14:45:00'),

-- Декабрь (выборочно)
(11, 4, 'present', '2024-12-02', 'lecture', '2024-12-02 09:00:00'),
(12, 4, 'present', '2024-12-02', 'practice', '2024-12-02 10:45:00'),
(13, 4, 'present', '2024-12-02', 'lab', '2024-12-02 13:00:00'),
(14, 4, 'absent', '2024-12-02', 'lecture', '2024-12-02 14:45:00');

-- Добавляем академическую успеваемость
INSERT INTO `academic_performance` (`student_id`, `course_id`, `semester`, `academic_year`, `final_grade`, `attendance_rate`) VALUES
(4, 1, 1, '2024-2025', 95.00, 98.50),
(4, 2, 1, '2024-2025', 91.00, 96.70),
(4, 3, 1, '2024-2025', 95.33, 97.80),
(4, 4, 1, '2024-2025', 87.67, 94.20),
(4, 5, 1, '2024-2025', 94.00, 95.90);

-- Добавляем итоговые показатели успеваемости
INSERT INTO `activity_log` (`user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `category`, `importance`, `status`) VALUES
(4, 'semester_summary', 'academic_performance', NULL, 'Средний балл за семестр: 92.6, Посещаемость: 96.62%', '127.0.0.1', 'academic', 'high', 'completed');

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` enum('lecture','computer','lab','practice') NOT NULL DEFAULT 'lecture',
  `capacity` int NOT NULL DEFAULT 30,
  `building` varchar(50) DEFAULT NULL,
  `floor` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `rooms` (`id`, `name`, `type`, `capacity`, `building`, `floor`) VALUES
(1, '301', 'lecture', 50, 'Главный корпус', 3),
(2, '302', 'computer', 30, 'Главный корпус', 3),
(3, '303', 'lab', 25, 'Главный корпус', 3),
(4, '304', 'practice', 40, 'Главный корпус', 3),
(5, '305', 'computer', 30, 'Главный корпус', 3);

-- Обновляем существующие данные
UPDATE students SET 
    faculty_id = 1,
    current_semester = 1,
    status = 'active'
WHERE user_id = 4;

-- Проверяем и обновляем данные в student_groups
DELETE FROM student_groups WHERE student_id = (SELECT id FROM students WHERE user_id = 4);
INSERT INTO student_groups (student_id, group_id, department_id, status, joined_date, name)
SELECT 
    s.id,
    1,
    1,
    'active',
    '2024-09-01',
    'ИСТ-21-1'
FROM students s
WHERE s.user_id = 4;

-- Проверяем и обновляем данные в student_group_members
DELETE FROM student_group_members WHERE student_id = (SELECT id FROM students WHERE user_id = 4);
INSERT INTO student_group_members (student_id, group_id, status, joined_date)
SELECT 
    s.id,
    1,
    'active',
    '2024-09-01'
FROM students s
WHERE s.user_id = 4;

-- Проверяем данные группы
UPDATE `groups` SET
    name = 'ИСТ-21-1',
    faculty = 'ФИТ',
    year_of_study = 2,
    curator_id = 2,
    department_id = 1,
    code = 'IST211'
WHERE id = 1;

-- Очищаем старые данные о посещаемости
DELETE FROM attendance WHERE student_id = (SELECT id FROM students WHERE user_id = 4);

-- Добавляем актуальные данные о посещаемости
INSERT INTO attendance (lesson_id, student_id, status, date, type) VALUES
-- Понедельник (15 января)
(4, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-15', 'lecture'),
(5, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-15', 'practice'),
(6, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-15', 'lab'),

-- Вторник (16 января)
(7, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-16', 'lecture'),
(8, (SELECT id FROM students WHERE user_id = 4), 'late', '2025-01-16', 'practice'),
(9, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-16', 'lab'),

-- Среда (17 января)
(10, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-17', 'lecture'),
(11, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-17', 'practice'),
(12, (SELECT id FROM students WHERE user_id = 4), 'late', '2025-01-17', 'lab'),

-- Четверг (18 января)
(13, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-18', 'lecture'),
(14, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-18', 'practice'),
(15, (SELECT id FROM students WHERE user_id = 4), 'absent', '2025-01-18', 'lab'),

-- Пятница (19 января)
(16, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-19', 'lecture'),
(17, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-19', 'practice'),
(18, (SELECT id FROM students WHERE user_id = 4), 'present', '2025-01-19', 'lab');

-- Добавляем записи о посещаемости за последний месяц
INSERT INTO attendance (lesson_id, student_id, status, date, type)
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-15',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 1
    AND s.start_time = '09:00:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-15',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 1
    AND s.start_time = '10:45:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-15',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 1
    AND s.start_time = '13:00:00';

-- Добавляем данные о посещаемости для вторника
INSERT INTO attendance (lesson_id, student_id, status, date, type)
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-16',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 2
    AND s.start_time = '09:00:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'late',
    '2025-01-16',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 2
    AND s.start_time = '10:45:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-16',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 2
    AND s.start_time = '13:00:00';

-- Добавляем данные о посещаемости для среды
INSERT INTO attendance (lesson_id, student_id, status, date, type)
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-17',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 3
    AND s.start_time = '09:00:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-17',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 3
    AND s.start_time = '10:45:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'late',
    '2025-01-17',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 3
    AND s.start_time = '13:00:00';

-- Добавляем данные о посещаемости для четверга
INSERT INTO attendance (lesson_id, student_id, status, date, type)
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-18',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 4
    AND s.start_time = '09:00:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-18',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 4
    AND s.start_time = '10:45:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'absent',
    '2025-01-18',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 4
    AND s.start_time = '13:00:00';

-- Добавляем данные о посещаемости для пятницы
INSERT INTO attendance (lesson_id, student_id, status, date, type)
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-19',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 5
    AND s.start_time = '09:00:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-19',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 5
    AND s.start_time = '10:45:00'
UNION ALL
SELECT 
    l.id,
    (SELECT id FROM students WHERE user_id = 4),
    'present',
    '2025-01-19',
    l.type
FROM lessons l
JOIN schedule s ON l.course_id = s.course_id 
    AND l.group_id = s.group_id 
    AND l.teacher_id = s.teacher_id
WHERE s.group_id = 1 
    AND s.day_of_week = 5
    AND s.start_time = '13:00:00';

-- Очищаем существующие данные
TRUNCATE TABLE `schedule`;
TRUNCATE TABLE `attendance`;

-- Создаем расписание на семестр
INSERT INTO `schedule` (`course_id`, `group_id`, `teacher_id`, `room`, `day_of_week`, `start_time`, `end_time`, `type`, `semester`, `year`, `is_active`) VALUES
-- Понедельник
(1, 1, 2, '301', 1, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),   -- Базы данных (лекция)
(1, 1, 2, '301', 1, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),  -- Базы данных (практика)
(2, 1, 2, '302', 1, '13:00:00', '14:30:00', 'lab', 1, 2025, 1),       -- Python (лаба)

-- Вторник
(3, 1, 3, '303', 2, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),   -- Веб-разработка (лекция)
(3, 1, 3, '303', 2, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),  -- Веб-разработка (практика)
(4, 1, 3, '304', 2, '13:00:00', '14:30:00', 'lecture', 1, 2025, 1),   -- Мат. анализ (лекция)

-- Среда
(2, 1, 2, '302', 3, '09:00:00', '10:30:00', 'lecture', 1, 2025, 1),   -- Python (лекция)
(2, 1, 2, '302', 3, '10:45:00', '12:15:00', 'practice', 1, 2025, 1),  -- Python (практика)
(5, 1, 2, '305', 3, '13:00:00', '14:30:00', 'lab', 1, 2025, 1),       -- Инф. безопасность (лаба)

-- Четверг
(4, 1, 3, '304', 4, '09:00:00', '10:30:00', 'practice', 1, 2025, 1),  -- Мат. анализ (практика)
(4, 1, 3, '304', 4, '10:45:00', '12:15:00', 'lecture', 1, 2025, 1),   -- Мат. анализ (лекция)
(5, 1, 2, '305', 4, '13:00:00', '14:30:00', 'lecture', 1, 2025, 1),   -- Инф. безопасность (лекция)

-- Пятница
(1, 1, 2, '301', 5, '09:00:00', '10:30:00', 'lab', 1, 2025, 1),       -- Базы данных (лаба)
(3, 1, 3, '303', 5, '10:45:00', '12:15:00', 'lab', 1, 2025, 1),       -- Веб-разработка (лаба)
(5, 1, 2, '305', 5, '13:00:00', '14:30:00', 'practice', 1, 2025, 1);  -- Инф. безопасность (практика)

-- Создаем уроки на основе расписания для первых двух недель семестра
INSERT INTO `lessons` (`course_id`, `teacher_id`, `group_id`, `type`, `topic`, `date`, `start_time`, `end_time`, `room`)
SELECT 
    s.course_id,
    s.teacher_id,
    s.group_id,
    s.type,
    CASE 
        WHEN c.name = 'Базы данных' AND s.type = 'lecture' THEN 'Введение в базы данных'
        WHEN c.name = 'Базы данных' AND s.type = 'practice' THEN 'Основы SQL'
        WHEN c.name = 'Базы данных' AND s.type = 'lab' THEN 'Работа с СУБД'
        WHEN c.name = 'Программирование на Python' AND s.type = 'lecture' THEN 'Основы Python'
        WHEN c.name = 'Программирование на Python' AND s.type = 'practice' THEN 'Работа со структурами данных'
        WHEN c.name = 'Программирование на Python' AND s.type = 'lab' THEN 'Разработка приложений'
        WHEN c.name = 'Веб-разработка' AND s.type = 'lecture' THEN 'HTML и CSS'
        WHEN c.name = 'Веб-разработка' AND s.type = 'practice' THEN 'Вёрстка макетов'
        WHEN c.name = 'Веб-разработка' AND s.type = 'lab' THEN 'Создание веб-приложений'
        WHEN c.name = 'Математический анализ' AND s.type = 'lecture' THEN 'Введение в анализ'
        WHEN c.name = 'Математический анализ' AND s.type = 'practice' THEN 'Решение задач'
        WHEN c.name = 'Информационная безопасность' AND s.type = 'lecture' THEN 'Основы безопасности'
        WHEN c.name = 'Информационная безопасность' AND s.type = 'practice' THEN 'Методы защиты'
        WHEN c.name = 'Информационная безопасность' AND s.type = 'lab' THEN 'Практика безопасности'
    END as topic,
    DATE_ADD('2025-01-15', INTERVAL (s.day_of_week - 1) DAY) as date,
    s.start_time,
    s.end_time,
    s.room
FROM schedule s
JOIN courses c ON s.course_id = c.id
WHERE s.is_active = 1
UNION ALL
SELECT 
    s.course_id,
    s.teacher_id,
    s.group_id,
    s.type,
    CASE 
        WHEN c.name = 'Базы данных' AND s.type = 'lecture' THEN 'Проектирование БД'
        WHEN c.name = 'Базы данных' AND s.type = 'practice' THEN 'Сложные запросы SQL'
        WHEN c.name = 'Базы данных' AND s.type = 'lab' THEN 'Оптимизация запросов'
        WHEN c.name = 'Программирование на Python' AND s.type = 'lecture' THEN 'ООП в Python'
        WHEN c.name = 'Программирование на Python' AND s.type = 'practice' THEN 'Работа с классами'
        WHEN c.name = 'Программирование на Python' AND s.type = 'lab' THEN 'Создание библиотек'
        WHEN c.name = 'Веб-разработка' AND s.type = 'lecture' THEN 'JavaScript основы'
        WHEN c.name = 'Веб-разработка' AND s.type = 'practice' THEN 'DOM манипуляции'
        WHEN c.name = 'Веб-разработка' AND s.type = 'lab' THEN 'SPA приложения'
        WHEN c.name = 'Математический анализ' AND s.type = 'lecture' THEN 'Пределы функций'
        WHEN c.name = 'Математический анализ' AND s.type = 'practice' THEN 'Вычисление пределов'
        WHEN c.name = 'Информационная безопасность' AND s.type = 'lecture' THEN 'Криптография'
        WHEN c.name = 'Информационная безопасность' AND s.type = 'practice' THEN 'Шифрование'
        WHEN c.name = 'Информационная безопасность' AND s.type = 'lab' THEN 'Защита данных'
    END as topic,
    DATE_ADD('2025-01-22', INTERVAL (s.day_of_week - 1) DAY) as date,
    s.start_time,
    s.end_time,
    s.room
FROM schedule s
JOIN courses c ON s.course_id = c.id
WHERE s.is_active = 1;

-- Создаем записи о посещаемости для каждого студента группы
INSERT INTO `attendance` (`student_id`, `lesson_id`, `status`, `date`, `type`)
SELECT 
    s.id as student_id,
    l.id as lesson_id,
    CASE 
        -- Для каждого типа занятия своя вероятность статусов
        WHEN l.type = 'lecture' THEN
            CASE 
                WHEN RAND() < 0.75 THEN 'present'
                WHEN RAND() < 0.15 THEN 'late'
                ELSE 'absent'
            END
        WHEN l.type = 'practice' THEN
            CASE 
                WHEN RAND() < 0.85 THEN 'present'
                WHEN RAND() < 0.10 THEN 'late'
                ELSE 'absent'
            END
        WHEN l.type = 'lab' THEN
            CASE 
                WHEN RAND() < 0.90 THEN 'present'
                WHEN RAND() < 0.08 THEN 'late'
                ELSE 'absent'
            END
        ELSE 'present'
    END as status,
    l.date,
    l.type
FROM lessons l
CROSS JOIN students s
JOIN student_groups sg ON s.id = sg.student_id
WHERE sg.group_id = 1
AND l.group_id = 1
ORDER BY l.date, l.start_time;

-- Добавляем пропущенные занятия для реалистичности
INSERT INTO `attendance` (`student_id`, `lesson_id`, `status`, `date`, `type`)
SELECT DISTINCT
    s.id as student_id,
    l.id as lesson_id,
    'absent' as status,
    l.date,
    l.type
FROM lessons l
CROSS JOIN students s
JOIN student_groups sg ON s.id = sg.student_id
WHERE sg.group_id = 1 
AND l.group_id = 1
AND RAND() < 0.1  -- 10% занятий будут пропущены
AND NOT EXISTS (
    SELECT 1 
    FROM attendance a 
    WHERE a.student_id = s.id 
    AND a.lesson_id = l.id
);

-- Добавляем опоздания
INSERT INTO `attendance` (`student_id`, `lesson_id`, `status`, `date`, `type`)
SELECT DISTINCT
    s.id as student_id,
    l.id as lesson_id,
    'late' as status,
    l.date,
    l.type
FROM lessons l
CROSS JOIN students s
JOIN student_groups sg ON s.id = sg.student_id
WHERE sg.group_id = 1 
AND l.group_id = 1
AND RAND() < 0.15  -- 15% занятий будут с опозданием
AND NOT EXISTS (
    SELECT 1 
    FROM attendance a 
    WHERE a.student_id = s.id 
    AND a.lesson_id = l.id
);

-- Обновляем academic_performance на основе посещаемости
INSERT INTO `academic_performance` (`student_id`, `course_id`, `semester`, `academic_year`, `attendance_rate`)
SELECT 
    s.id as student_id,
    c.id as course_id,
    1 as semester,
    '2024-2025' as academic_year,
    ROUND(
        (COUNT(CASE WHEN a.status IN ('present', 'late') THEN 1 END) * 100.0 / COUNT(*)),
        2
    ) as attendance_rate
FROM students s
CROSS JOIN courses c
LEFT JOIN lessons l ON l.course_id = c.id
LEFT JOIN attendance a ON a.lesson_id = l.id AND a.student_id = s.id
JOIN student_groups sg ON s.id = sg.student_id
WHERE sg.group_id = 1
GROUP BY s.id, c.id
ON DUPLICATE KEY UPDATE
    attendance_rate = VALUES(attendance_rate);