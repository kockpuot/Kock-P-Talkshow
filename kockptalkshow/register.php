<?php
session_start();
require_once 'database/kock_p_talkshow.php';
require_once 'includes/sms.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $desired_role = $_POST['desired_role'] ?? 'user';

    if (empty($title) || empty($fullname) || empty($username) || empty($email) || empty($phone) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Check if username or email already exists in users table or pending
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? UNION SELECT id FROM pending_registrations WHERE username = ? OR email = ?");
        $check->execute([$username, $email, $username, $email]);
        if ($check->fetch()) {
            $error = "Username or email already taken or pending approval.";
        } else {
            $token = bin2hex(random_bytes(32));
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO pending_registrations (title, fullname, username, email, phone, password_hash, desired_role, token, status) VALUES (?,?,?,?,?,?,?,?, 'pending')");
            $stmt->execute([$title, $fullname, $username, $email, $phone, $password_hash, $desired_role, $token]);

            // Send SMS to admin
            $adminPhone = '+251993557112';
            $message = "New registration request:\nName: $title $fullname\nUsername: $username\nEmail: $email\nRole: $desired_role\nApprove: http://localhost/kockptalkshow/admin/approve_registration.php?token=$token&action=approve\nReject: http://localhost/kockptalkshow/admin/approve_registration.php?token=$token&action=reject";
            sendSMS($adminPhone, $message);

            $success = "Registration request submitted. Admin will review and notify you via SMS/email once approved.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reuse styles from login.php */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', sans-serif; background:#f9f9f9; }
        .container { max-width:600px; margin:50px auto; padding:0 20px; }
        .form-card { background:white; padding:2rem; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
        h1 { text-align:center; margin-bottom:1.5rem; }
        .form-group { margin-bottom:1rem; }
        label { display:block; font-weight:600; margin-bottom:0.3rem; }
        input, select { width:100%; padding:10px; border:1px solid #ddd; border-radius:12px; }
        button { background:#e94560; color:white; border:none; padding:12px; border-radius:40px; width:100%; font-size:1rem; cursor:pointer; }
        .alert { padding:10px; border-radius:12px; margin-bottom:1rem; }
        .alert-error { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
        .alert-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
        p { text-align:center; margin-top:1rem; }
        a { color:#e94560; }
    </style>
</head>
<body>
<div class="container">
    <div class="form-card">
        <h1>Register for Kock P Talkshow</h1>
        <?php if($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Title</label>
                <select name="title">
                    <option value="Mr.">Mr.</option><option value="Mrs.">Mrs.</option><option value="Ms.">Ms.</option>
                    <option value="Hon.">Hon.</option><option value="Amb.">Amb.</option><option value="Dr.">Dr.</option><option value="Prof.">Prof.</option>
                </select>
            </div>
            <div class="form-group"><label>Full Name *</label><input type="text" name="fullname" required></div>
            <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Phone *</label><input type="tel" name="phone" required></div>
            <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" required></div>
            <div class="form-group"><label>Desired Role</label>
                <select name="desired_role"><option value="user">User</option><option value="editor">Editor</option></select>
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>
</body>
</html>