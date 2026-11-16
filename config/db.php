<?php
// ============================================================
// KRK Polytechnic Hostel Portal — Database Configuration
// ============================================================

define('DB_HOST', 'sql105.infinityfree.com');
define('DB_USER', 'if0_43448913');
define('DB_PASS', '159357Ayomikun');
define('DB_NAME', 'if0_43448913_hostel_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ---- Define BASE_URL globally (available to all files that include db.php) ----
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    // On localhost XAMPP the project lives at /hostel_portal/
    // On InfinityFree (krkpolytechnic.rf.gd) files are at the htdocs root — no subfolder
    $base = (strpos($script, '/hostel_portal/') !== false) ? '/hostel_portal' : '';
    define('BASE_URL', $scheme . '://' . $host . $base);
}

// ---- Ensure settings table exists (created lazily) ----
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed defaults (INSERT IGNORE = skip if key already exists)
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('academic_session',  '2025/2026'),
    ('hostel_fee_amount', '25000'),
    ('registration_open', '1'),
    ('portal_name',       'KRK Polytechnic Hostel Portal'),
    ('max_bed_spaces',    '200')
");

// ---- Convenience function to read a single setting ----
function setting(mysqli $db, string $key, string $default = ''): string {
    $st = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $st->bind_param("s", $key);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ? $row['setting_value'] : $default;
}
