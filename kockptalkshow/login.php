<?php
// login.php - Frontend user login page
session_start();
require_once 'database/kock_p_talkshow.php';

// Redirect if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: my_account.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        // Fetch user from approved users table only
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        // If user exists and password matches
        if ($user && password_verify($password, $user['password'])) {
            // Optional: Check if user is active (if column exists)
            // if (isset($user['status']) && $user['status'] !== 'active') {
            //     $error = "Your account is not active. Contact admin.";
            // } else {
                // Login successful
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: my_account.php');
                }
                exit;
            // }
        } else {
            // Also check pending registrations to give a friendly message
            $stmt = $pdo->prepare("SELECT status FROM pending_registrations WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $pending = $stmt->fetch();
            if ($pending) {
                $error = "Your registration is still pending approval. You will be notified once approved.";
            } else {
                $error = "Invalid username/email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<!-- Rest of the HTML remains exactly as before -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Keep your existing styles – same as before */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', sans-serif; background:#f9f9f9; color:#222; }
        .container { max-width:1200px; margin:auto; padding:0 20px; }
        header { background:#1a1a2e; color:white; padding:1rem 0; position:sticky; top:0; z-index:100; }
        nav { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .logo { display:flex; align-items:center; gap:10px; font-size:1.8rem; font-weight:bold; }
        .logo img { height:50px; width:auto; }
        .logo span { color:#e94560; }
        .nav-links { display:flex; gap:1.5rem; list-style:none; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-links a:hover, .nav-links a.active { color:#e94560; }
        .page-title { text-align:center; margin:2rem 0 1rem; font-size:2.5rem; }
        .form-card { background:white; max-width:500px; margin:0 auto 3rem; padding:2rem; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
        .form-group { margin-bottom:1.5rem; }
        label { display:block; margin-bottom:0.5rem; font-weight:600; }
        input { width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:12px; font-size:1rem; }
        input:focus { outline:none; border-color:#e94560; box-shadow:0 0 0 3px rgba(233,69,96,0.1); }
        .btn-submit { background:#e94560; color:white; border:none; padding:14px 28px; border-radius:40px; font-size:1.1rem; font-weight:bold; cursor:pointer; width:100%; }
        .btn-submit:hover { background:#ff6b6b; }
        .alert-error { background:#f8d7da; color:#721c24; padding:12px 20px; border-radius:12px; margin-bottom:1.5rem; border-left:4px solid #dc3545; }
        .info-text { text-align:center; margin-top:1.5rem; color:#555; }
        .info-text a { color:#e94560; text-decoration:none; }
        footer { background:#0a0a1a; color:#ccc; text-align:center; padding:2rem; margin-top:3rem; }
        .social-links a { color:#ccc; margin:0 10px; font-size:1.5rem; transition:0.3s; display:inline-block; }
        .social-links a:hover { color:#e94560; }
        @media (max-width:768px) { .form-card { padding:1.5rem; } .nav-links { margin-top:1rem; justify-content:center; width:100%; } .logo { font-size:1.2rem; } .logo img { height:35px; } }
    </style>
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo">
                <img src="images/logo.png" alt="Kock P Talkshow Logo">
                Kock P <span>Talkshow</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="shows.php">Shows</a></li>
                <li><a href="request_talk.php">Request Talk</a></li>
                <li><a href="share_history.php">Share Story</a></li>
                <li><a href="advertise.php">Advertise</a></li>
                <li><a href="suggest_guest.php">Suggest Guest</a></li>
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                    <li><a href="my_account.php">My Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="active">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h1 class="page-title">🔐 Login to Your Account</h1>

    <div class="form-card">
        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Login <i class="fas fa-sign-in-alt"></i></button>
        </form>

        <div class="info-text">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 Kock P Talkshow – Join the conversation.</p>
        <div class="social-links">
            <a href="https://facebook.com/kockp01" target="_blank"><i class="fab fa-facebook"></i></a>
            <a href="https://youtube.com/@kockp01" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://instagram.com/kockp01" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://tiktok.com/@kockp01" target="_blank"><i class="fab fa-tiktok"></i></a>
            <a href="https://wa.me/251993557112" target="_blank"><i class="fab fa-whatsapp"></i></a>
            <a href="https://t.me/kockpuot" target="_blank"><i class="fab fa-telegram"></i></a>
            <a href="mailto:kockpuot@gmail.com"><i class="fas fa-envelope"></i></a>
        </div>
        <div class="contact-info">
            <p>Email: kockpuot@gmail.com | WhatsApp: +251993557112 | Telegram: @kockpuot</p>
        </div>
    </div>
</footer>

</body>
</html>