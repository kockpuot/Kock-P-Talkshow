<?php
session_start();
require_once '../database/kock_p_talkshow.php';

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

if (!$token || !in_array($action, ['approve', 'reject'])) {
    die("Invalid request.");
}

$stmt = $pdo->prepare("SELECT * FROM pending_registrations WHERE token = ? AND status = 'pending'");
$stmt->execute([$token]);
$pending = $stmt->fetch();

if (!$pending) {
    die("Request already processed or invalid token.");
}

if ($action === 'approve') {
    // Insert into users table
    $insert = $pdo->prepare("INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([
        $pending['username'],
        $pending['password_hash'],
        $pending['email'],
        $pending['desired_role']
    ]);
    $status = 'approved';
    $message = "Your registration has been APPROVED. You can now login at http://localhost/kockptalkshow/login.php";
} else {
    $status = 'rejected';
    $message = "Your registration request has been REJECTED.";
}

// Update pending status
$update = $pdo->prepare("UPDATE pending_registrations SET status = ? WHERE id = ?");
$update->execute([$status, $pending['id']]);

// Send SMS notification to user (optional – uses phone from pending)
// Include a simple SMS function (same as before) – we can reuse
require_once '../includes/sms.php';
sendSMS($pending['phone'], $message);

echo "<h2>Registration $status</h2><p>User notified via SMS.</p><a href='dashboard.php'>Back to Dashboard</a>";
?>