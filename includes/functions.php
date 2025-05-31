function getStatusClass($status) {
    switch ($status) {
        case 'present':
            return 'success';
        case 'late':
            return 'warning';
        case 'absent':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getStatusName($status) {
    switch ($status) {
        case 'present':
            return 'Присутствовал';
        case 'late':
            return 'Опоздал';
        case 'absent':
            return 'Отсутствовал';
        default:
            return 'Неизвестно';
    }
}

function getLessonTypeClass($type) {
    switch ($type) {
        case 'lecture':
            return 'primary';
        case 'practice':
            return 'success';
        case 'lab':
            return 'info';
        default:
            return 'secondary';
    }
}

function getLessonType($type) {
    switch ($type) {
        case 'lecture':
            return 'Лекция';
        case 'practice':
            return 'Практика';
        case 'lab':
            return 'Лабораторная';
        default:
            return 'Неизвестно';
    }
} 