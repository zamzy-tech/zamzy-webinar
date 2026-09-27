<?php
// submit-contact.php - API endpoint to store contact form messages
define('API_REQUEST', true);
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/db.php';

// Support both JSON body and standard Form POST
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

$name = trim($jsonData['name'] ?? $_POST['name'] ?? '');
$email = trim($jsonData['email'] ?? $_POST['email'] ?? '');
$subject = trim($jsonData['subject'] ?? $_POST['subject'] ?? 'General Inquiry');
$message = trim($jsonData['message'] ?? $_POST['message'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (Name, Email, Message).']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

try {
    $stmt = $pdo->prepare("INSERT INTO `contact_messages` (`name`, `email`, `subject`, `message`, `ip_address`, `status`) VALUES (?, ?, ?, ?, ?, 'new')");
    $stmt->execute([$name, $email, $subject, $message, $ip_address]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been received and saved successfully.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save message. Please try again.']);
}
