<?php
function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: ' . url('index.php'));
        exit();
    }
}

function redirectIfNotAuthorized($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: ' . url('index.php'));
        exit();
    }
}

function getDashboardUrl() {
    if (!isset($_SESSION['role'])) {
        return url('index.php');
    }

    switch ($_SESSION['role']) {
        case 'admin':
            return url('views/admin/dashboard.php');
        case 'medecin':
            return url('views/medecin/dashboard.php');
        case 'patient':
            return url('views/patient/dashboard.php');
        default:
            return url('index.php');
    }
}
?> 