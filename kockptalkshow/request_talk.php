<?php
// request_talk.php - Public form to request a talk + display approved talk requests
session_start(); // for potential user info
require_once 'database/kock_p_talkshow.php';

// Handle form submission (same as before, but with improved DB error handling)
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($contact) || empty($topic) || empty($type) || empty($description)) {
        $error = "All fields are required.";
    } elseif (!filter_var($contact, FILTER_VALIDATE_EMAIL) && !preg_match('/^[0-9+\-\s()]+$/', $contact)) {
        $error = "Please enter a valid email address or phone number.";
    } else {
        try {
            $sql = "INSERT INTO talk_requests (name, contact, topic, type, description, status, created_at) 
                    VALUES (:name, :contact, :topic, :type, :description, 'pending', NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':contact' => $contact,
                ':topic' => $topic,
                ':type' => $type,
                ':description' => $description
            ]);
            $success = "✅ Thank you, $name! Your request has been submitted. We'll contact you soon.";
            // Clear form
            $name = $contact = $topic = $type = $description = '';
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch approved talk requests to display as "posted"
$approvedTalks = $pdo->query("SELECT * FROM talk_requests WHERE status = 'approved' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request a Talk – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reuse the same styles from previous version – included for completeness */
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
        .subtitle { text-align:center; color:#555; margin-bottom:2rem; }
        .form-card { background:white; max-width:700px; margin:0 auto 3rem; padding:2rem; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
        .form-group { margin-bottom:1.5rem; }
        label { display:block; margin-bottom:0.5rem; font-weight:600; }
        input, select, textarea { width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:12px; font-size:1rem; }
        textarea { resize:vertical; min-height:100px; }
        .btn-submit { background:#e94560; color:white; border:none; padding:14px 28px; border-radius:40px; font-size:1.1rem; font-weight:bold; cursor:pointer; width:100%; }
        .alert { padding:12px 20px; border-radius:12px; margin-bottom:1.5rem; }
        .alert-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
        .alert-error { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
        .info-box { background:#f0f2f5; border-radius:20px; padding:1.5rem; margin-top:1rem; text-align:center; }
        .talks-list { margin:2rem 0; }
        .talk-card { background:white; border-radius:16px; padding:1.2rem; margin-bottom:1rem; box-shadow:0 2px 8px rgba(0,0,0,0.05); border-left:4px solid #e94560; }
        .talk-title { font-size:1.2rem; font-weight:bold; margin-bottom:0.3rem; }
        .talk-meta { font-size:0.8rem; color:#666; margin-bottom:0.5rem; }
        .talk-desc { color:#333; }
        footer { background:#0a0a1a; color:#ccc; text-align:center; padding:2rem; margin-top:3rem; }
        .social-links a { color:#ccc; margin:0 10px; font-size:1.5rem; display:inline-block; }
        .social-links a:hover { color:#e94560; }
        @media (max-width:768px) { .form-card { padding:1.5rem; } .nav-links { margin-top:1rem; justify-content:center; width:100%; } .logo { font-size:1.2rem; } .logo img { height:35px; } }
    </style>
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo">
             <img src="images/logo.png" alt=" ">
                Kock P <span>Talkshow</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="shows.php">Shows</a></li>
                <li><a href="request_talk.php" class="active">Request Talk</a></li>
                <li><a href="share_history.php">Share Story</a></li>
                <li><a href="advertise.php">Software project</a></li>
                <li><a href="suggest_guest.php">Suggest Guest</a></li>
                <?php if(isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']===true): ?>
                    <li><a href="my_account.php">My Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h1 class="page-title">🎤 Request a Talk</h1>
    <p class="subtitle">Want to be a guest? Have an important topic? Fill out the form below.</p>

    <div class="form-card">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="contact">Email or Phone Number *</label>
                <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($contact ?? ''); ?>" required>
                <small style="color:#666;">We'll reach you here.</small>
            </div>
            <div class="form-group">
                <label for="topic">Topic / Title of Discussion *</label>
                <input type="text" id="topic" name="topic" value="<?php echo htmlspecialchars($topic ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="type">Type of Talk *</label>
                <select id="type" name="type" required>
                    <option value="">-- Select --</option>
                    <option value="interview" <?php echo (isset($type) && $type == 'interview') ? 'selected' : ''; ?>>🎙️ Interview</option>
                    <option value="podcast" <?php echo (isset($type) && $type == 'podcast') ? 'selected' : ''; ?>>🎧 Podcast</option>
                    <option value="special" <?php echo (isset($type) && $type == 'special') ? 'selected' : ''; ?>>💬 Special Talk</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Brief Description / Why this talk? *</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn-submit">Submit Request <i class="fas fa-paper-plane"></i></button>
        </form>

        <div class="info-box">
            <i class="fas fa-microphone-alt" style="font-size:2rem; color:#e94560;"></i>
            <p style="margin-top:10px;">All requests are reviewed by our team. If selected, we'll contact you within 5–7 business days to schedule.</p>
        </div>
    </div>

    <!-- Display Approved Talk Requests as "Posted" -->
    <?php if (count($approvedTalks) > 0): ?>
        <h2 class="page-title" style="font-size:1.8rem; margin-top:1rem;">📢 Approved & Upcoming Talks</h2>
        <div class="talks-list">
            <?php foreach ($approvedTalks as $talk): ?>
                <div class="talk-card">
                    <div class="talk-title"><?php echo htmlspecialchars($talk['topic']); ?></div>
                    <div class="talk-meta">
                        <span>🎙️ <?php echo ucfirst($talk['type']); ?></span> | 
                        <span>👤 Requested by: <?php echo htmlspecialchars($talk['name']); ?></span> | 
                        <span>📅 <?php echo date('M d, Y', strtotime($talk['created_at'])); ?></span>
                    </div>
                    <div class="talk-desc"><?php echo nl2br(htmlspecialchars($talk['description'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center; margin:2rem 0;">No approved talks yet. Check back soon!</p>
    <?php endif; ?>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 Kock P Talkshow – Your voice matters.</p>
        <div class="social-links">
            <a href="https://facebook.com/kockp01"><i class="fab fa-facebook"></i></a>
            <a href="https://youtube.com/@kockp01"><i class="fab fa-youtube"></i></a>
            <a href="https://instagram.com/kockp01"><i class="fab fa-instagram"></i></a>
            <a href="https://tiktok.com/@kockp01"><i class="fab fa-tiktok"></i></a>
            <a href="https://wa.me/251993557112"><i class="fab fa-whatsapp"></i></a>
            <a href="https://t.me/kockpuot"><i class="fab fa-telegram"></i></a>
            <a href="mailto:kockpuot@gmail.com"><i class="fas fa-envelope"></i></a>
        </div>
        <p>Email: kockpuot@gmail.com | WhatsApp: +251993557112 | Telegram: @kockpuot</p>
    </div>
</footer>

</body>
</html>