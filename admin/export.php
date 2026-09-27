<?php
// export.php - Export contact messages to CSV
session_start();
if (!isset($_SESSION['zamzy_admin_logged']) || $_SESSION['zamzy_admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=zamzy_webinar_contacts_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');
// Output column headers
fputcsv($output, ['ID', 'Full Name', 'Email Address', 'Subject', 'Message', 'Status', 'IP Address', 'Received Date']);

$stmt = $pdo->query("SELECT id, name, email, subject, message, status, ip_address, created_at FROM contact_messages ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    fputcsv($output, $row);
}
fclose($output);
exit;
