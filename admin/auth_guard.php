<?php
/**
 * auth_guard.php — Central role-based access control for admin pages.
 * Include AFTER session_start() and before any output.
 *
 * Usage:
 *   require_once 'auth_guard.php';
 *   require_admin();                        // any logged-in admin
 *   require_role(['dsa']);                  // DSA only
 *   require_role(['dsa','registrar']);      // DSA or Registrar
 */

if (!function_exists('require_admin')) {

    function require_admin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['admin_id'])) {
            header('Location: login.php'); exit();
        }
    }

    /**
     * @param string[] $roles   Allowed roles, e.g. ['dsa','registrar']
     * @param string   $msg     Optional flash message shown on redirect
     */
    function require_role(array $roles, string $msg = '') {
        require_admin();
        $role = $_SESSION['admin_role'] ?? '';
        if (!in_array($role, $roles, true)) {
            $_SESSION['access_error'] = $msg ?: 'You do not have permission to access that page.';
            header('Location: dashboard.php'); exit();
        }
    }

    /**
     * Returns true if the current admin has at least one of the given roles.
     */
    function admin_can(array $roles): bool {
        $role = $_SESSION['admin_role'] ?? '';
        return in_array($role, $roles, true);
    }
}
?>
