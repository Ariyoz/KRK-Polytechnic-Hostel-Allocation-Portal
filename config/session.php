<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireStudentLogin() {
    if (!isset($_SESSION['student_id'])) {
        header("Location: /student/login.php");
        exit();
    }
}

function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: /admin/login.php");
        exit();
    }
}

function requireRole($role) {
    requireAdminLogin();
    if ($_SESSION['admin_role'] !== $role && $_SESSION['admin_role'] !== 'dsa') {
        header("Location: /admin/dashboard.php?error=unauthorized");
        exit();
    }
}
?>
