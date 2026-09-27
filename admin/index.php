<?php
// admin/index.php - Route to dashboard or login
session_start();
if (isset($_SESSION['zamzy_admin_logged']) && $_SESSION['zamzy_admin_logged'] === true) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
