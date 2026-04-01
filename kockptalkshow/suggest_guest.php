<?php
// future_guests.php - Display all approved guest suggestions + admin add form
session_start();
require_once 'database/kock_p_talkshow.php';

// Determine admin status (adjust according to your login system)
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle admin form submission
$add_success = '';
$add_error = '';
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_guest'])) {
    $guest_name = trim($_POST['guest_name'] ?? '');
    $profession = trim($_POST['profession'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if (empty($guest_name) || empty($profession) || empty($reason)) {
        $add_error = "Guest name, profession, and reason are required.";
    } else {
        $guest_image = null;
        if (isset($_FILES['guest_image']) && $_FILES['guest_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tmp = $_FILES['guest_image']['tmp_name'];
            $type = mime_content_type($tmp);
            if (in_array($type, $allowed)) {
                $upload_dir = __DIR__ . '/uploads/guests/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['guest_image']['name'], PATHINFO_EXTENSION);
                $new_name = uniqid('guest_', true) . '.' . $ext;
                if (move_uploaded_file($tmp, $upload_dir . $new_name)) {
                    $guest_image = 'uploads/guests/' . $new_name;
                } else {
                    $add_error = "Failed to upload image.";
                }
            } else {
                $add_error = "Invalid image type. Allowed: JPG, PNG, GIF, WEBP.";
            }
        }

        if (empty($add_error)) {
            try {
                $sql = "INSERT INTO guest_suggestions 
                        (guest_name, profession, reason, guest_image, status, created_at) 
                        VALUES (:name, :prof, :reason, :img, 'approved', NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $guest_name,
                    ':prof' => $profession,
                    ':reason' => $reason,
                    ':img' => $guest_image
                ]);
                $add_success = "✅ Guest added successfully!";
                // Optionally clear form fields by redirecting or not repopulating
            } catch (PDOException $e) {
                $add_error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch approved guests (including newly added ones)
$approvedGuests = $pdo->query("SELECT * FROM guest_suggestions WHERE status = 'approved' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Future Guests – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', sans-serif; background:#f9f9f9; }
        .container { max-width:1200px; margin:auto; padding:0 20px; }
        header { background:#1a1a2e; color:white; padding:1rem 0; }
        nav { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .logo { display:flex; align-items:center; gap:10px; font-size:1.8rem; font-weight:bold; }
        .logo img { height:50px; }
        .logo span { color:#e94560; }
        .nav-links { display:flex; gap:1.5rem; list-style:none; }
        .nav-links a { color:white; text-decoration:none; }
        .nav-links a:hover { color:#e94560; }
        .page-title { text-align:center; margin:2rem 0; }
        
        /* Admin form styles */
        .admin-section {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            margin: 2rem 0;
            padding: 1.5rem;
        }
        .admin-section h2 {
            color: #1a1a2e;
            margin-bottom: 1rem;
            border-left: 4px solid #e94560;
            padding-left: 1rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .btn-submit {
            background: #e94560;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-submit:hover {
            background: #ff6b6b;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .guest-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        .guest-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .guest-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #1a1a2e;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .guest-content {
            padding: 1.2rem;
        }
        .guest-name {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .guest-profession {
            color: #e94560;
            margin: 0.3rem 0;
        }
        footer {
            background: #0a0a1a;
            color: #ccc;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }
        @media (max-width:768px) {
            .nav-links {
                margin-top: 1rem;
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="logo"><img src="images/logo.png" alt="Logo"> Kock P <span>Talkshow</span></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="shows.php">Shows</a></li>
                <li><a href="request_talk.php">Request Talk</a></li>
                <li><a href="share_history.php">Share Story</a></li>
                <li><a href="advertise.php">Software projects</a></li>
                <li><a href="suggest_guest.php">Suggest Guest</a></li>
                <?php if(isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']===true) echo '<li><a href="my_account.php">My Account</a></li><li><a href="logout.php">Logout</a></li>'; else echo '<li><a href="login.php">Login</a></li>'; ?>
            </ul>
        </nav>
    </div>
</header>
<div class="container">
    <h1 class="page-title">🌟 Future Guests on Kock P Talkshow</h1>
    
    <!-- Admin add form (visible only to admin) -->
    <?php if ($is_admin): ?>
        <div class="admin-section">
            <h2><i class="fas fa-user-plus"></i> Add New Guest (Admin Only)</h2>
            <?php if ($add_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($add_success); ?></div>
            <?php elseif ($add_error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($add_error); ?></div>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="guest_name">Guest Name *</label>
                    <input type="text" id="guest_name" name="guest_name" required>
                </div>
                <div class="form-group">
                    <label for="profession">Profession / Title *</label>
                    <input type="text" id="profession" name="profession" required>
                </div>
                <div class="form-group">
                    <label for="reason">Why they are a great guest? *</label>
                    <textarea id="reason" name="reason" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="guest_image">Guest Photo (optional)</label>
                    <input type="file" id="guest_image" name="guest_image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small>Allowed: JPG, PNG, GIF, WEBP</small>
                </div>
                <button type="submit" name="add_guest" class="btn-submit">Add Guest</button>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- Display approved guests -->
    <?php if (count($approvedGuests) > 0): ?>
        <div class="guest-grid">
            <?php foreach ($approvedGuests as $guest): ?>
                <div class="guest-card">
                    <?php if (!empty($guest['guest_image']) && file_exists($guest['guest_image'])): ?>
                        <img class="guest-img" src="<?php echo htmlspecialchars($guest['guest_image']); ?>" alt="<?php echo htmlspecialchars($guest['guest_name']); ?>">
                    <?php else: ?>
                        <div class="guest-img"><i class="fas fa-user-circle" style="font-size:3rem;"></i></div>
                    <?php endif; ?>
                    <div class="guest-content">
                        <div class="guest-name"><?php echo htmlspecialchars($guest['guest_name']); ?></div>
                        <div class="guest-profession"><?php echo htmlspecialchars($guest['profession']); ?></div>
                        <p><?php echo nl2br(htmlspecialchars(substr($guest['reason'], 0, 120))) . (strlen($guest['reason']) > 120 ? '...' : ''); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;">No approved guest suggestions yet. <?php if ($is_admin) echo 'Use the form above to add one.'; else echo '<a href="suggest_guest.php">Suggest a guest</a>.'; ?></p>
    <?php endif; ?>
</div>
<footer><div class="container"><p>&copy; 2025 Kock P Talkshow</p></div></footer>
</body>
</html>