<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$script = $_SERVER['SCRIPT_NAME'];
$base   = (strpos($script, '/hostel_portal/') !== false) ? '/hostel_portal' : '';

header("Location: " . $scheme . "://" . $host . $base . "/admin/login.php");
exit();
