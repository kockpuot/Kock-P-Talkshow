<?php
// share_history.php - Public form to share historical stories/events
// Includes file upload, DB storage, admin approval workflow, plus admin add form and story gallery

session_start();

// Database configuration
$host = 'localhost';
$dbname = 'kock_p_talkshow';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
    $db_error = "Database connection failed: " . $e->getMessage();
}

// Determine if current user is admin (adjust session variable as needed)
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// ==================== PUBLIC FORM SUBMISSION (pending) ====================
$success = '';
$error = '';
$uploaded_file_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_add'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $submitter_name = trim($_POST['submitter_name'] ?? '');
    $submitter_contact = trim($_POST['submitter_contact'] ?? '');

    if (empty($title) || empty($description) || empty($submitter_name)) {
        $error = "Title, description, and your name are required.";
    } else {
        // File upload handling (same as before)
        $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
        $max_file_size = 10 * 1024 * 1024;

        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['media_file']['tmp_name'];
            $file_name = basename($_FILES['media_file']['name']);
            $file_size = $_FILES['media_file']['size'];
            $file_type = mime_content_type($file_tmp);
            
            if (in_array($file_type, $allowed_image_types) || in_array($file_type, $allowed_video_types)) {
                if ($file_size <= $max_file_size) {
                    $upload_dir = __DIR__ . '/uploads/history/';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_filename = uniqid('history_', true) . '.' . $ext;
                    $destination = $upload_dir . $new_filename;
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $uploaded_file_path = 'uploads/history/' . $new_filename;
                    } else {
                        $error = "Failed to upload file. Check directory permissions.";
                    }
                } else {
                    $error = "File too large. Max 10MB.";
                }
            } else {
                $error = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP, MP4, WEBM, OGG.";
            }
        } elseif (isset($_FILES['media_file']) && $_FILES['media_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = "File upload error: " . $_FILES['media_file']['error'];
        }

        if (empty($error) && $pdo) {
            try {
                $sql = "INSERT INTO history_submissions 
                        (title, description, location, event_date, media_path, submitter_name, submitter_contact, status, created_at) 
                        VALUES (:title, :description, :location, :event_date, :media_path, :submitter_name, :submitter_contact, 'pending', NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':location' => $location ?: null,
                    ':event_date' => $event_date ?: null,
                    ':media_path' => $uploaded_file_path ?: null,
                    ':submitter_name' => $submitter_name,
                    ':submitter_contact' => $submitter_contact ?: null
                ]);
                $success = "✅ Thank you, $submitter_name! Your story has been submitted and will be reviewed by our team.";
                // Clear form fields
                $title = $description = $location = $event_date = $submitter_name = $submitter_contact = '';
                $uploaded_file_path = '';
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } elseif (empty($error) && !$pdo) {
            $error = $db_error ?? "Database not available. Please try later.";
        }
    }
}

// ==================== ADMIN FORM SUBMISSION (immediate with chosen status) ====================
$admin_success = '';
$admin_error = '';
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_add'])) {
    $title = trim($_POST['admin_title'] ?? '');
    $description = trim($_POST['admin_description'] ?? '');
    $location = trim($_POST['admin_location'] ?? '');
    $event_date = trim($_POST['admin_event_date'] ?? '');
    $submitter_name = trim($_POST['admin_submitter_name'] ?? '');
    $submitter_contact = trim($_POST['admin_submitter_contact'] ?? '');
    $status = $_POST['admin_status'] ?? 'approved';

    if (empty($title) || empty($description) || empty($submitter_name)) {
        $admin_error = "Title, description, and submitter name are required.";
    } else {
        $uploaded_file_path = null;
        if (isset($_FILES['admin_media_file']) && $_FILES['admin_media_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowed_video_types = ['video/mp4', 'video/webm', 'video/ogg'];
            $max_file_size = 10 * 1024 * 1024;
            $file_tmp = $_FILES['admin_media_file']['tmp_name'];
            $file_type = mime_content_type($file_tmp);
            $file_size = $_FILES['admin_media_file']['size'];

            if ((in_array($file_type, $allowed_image_types) || in_array($file_type, $allowed_video_types)) && $file_size <= $max_file_size) {
                $upload_dir = __DIR__ . '/uploads/history/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['admin_media_file']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('history_', true) . '.' . $ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $uploaded_file_path = 'uploads/history/' . $new_filename;
                } else {
                    $admin_error = "Failed to upload file.";
                }
            } else {
                $admin_error = "Invalid file type or size >10MB.";
            }
        }

        if (empty($admin_error) && $pdo) {
            try {
                $sql = "INSERT INTO history_submissions 
                        (title, description, location, event_date, media_path, submitter_name, submitter_contact, status, created_at) 
                        VALUES (:title, :description, :location, :event_date, :media_path, :submitter_name, :submitter_contact, :status, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':location' => $location ?: null,
                    ':event_date' => $event_date ?: null,
                    ':media_path' => $uploaded_file_path,
                    ':submitter_name' => $submitter_name,
                    ':submitter_contact' => $submitter_contact ?: null,
                    ':status' => $status
                ]);
                $admin_success = "✅ Story added successfully!";
            } catch (PDOException $e) {
                $admin_error = "Database error: " . $e->getMessage();
            }
        } elseif (empty($admin_error) && !$pdo) {
            $admin_error = $db_error ?? "Database not available.";
        }
    }
}

// ==================== FETCH APPROVED STORIES ====================
$stories = [];
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM history_submissions WHERE status = 'approved' ORDER BY created_at DESC");
    $stmt->execute();
    $stories = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share History – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f9f9f9; color:#222; }
        .container { max-width:1200px; margin:auto; padding:0 20px; }
        header { background:#1a1a2e; color:white; padding:1rem 0; position:sticky; top:0; z-index:100; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        nav { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .logo { font-size:1.8rem; font-weight:bold; }
        .logo span { color:#e94560; }
        .nav-links { display:flex; gap:1.5rem; list-style:none; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-links a:hover, .nav-links a.active { color:#e94560; }
        .page-title { text-align:center; margin:2rem 0 1rem; font-size:2.5rem; }
        .subtitle { text-align:center; color:#555; margin-bottom:2rem; }
        .form-card { background:white; max-width:800px; margin:0 auto 3rem; padding:2rem; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
        .admin-section { border:2px solid #e94560; margin-bottom:2rem; }
        .form-group { margin-bottom:1.5rem; }
        label { display:block; margin-bottom:0.5rem; font-weight:600; }
        input, select, textarea { width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:12px; font-size:1rem; font-family:inherit; transition:0.2s; }
        input:focus, select:focus, textarea:focus { outline:none; border-color:#e94560; box-shadow:0 0 0 3px rgba(233,69,96,0.1); }
        textarea { resize:vertical; min-height:120px; }
        .file-input { padding:10px 0; }
        .file-input small { display:block; color:#666; margin-top:5px; }
        .btn-submit { background:#e94560; color:white; border:none; padding:14px 28px; border-radius:40px; font-size:1.1rem; font-weight:bold; cursor:pointer; width:100%; transition:0.3s; }
        .btn-submit:hover { background:#ff6b6b; transform:scale(1.01); }
        .alert { padding:12px 20px; border-radius:12px; margin-bottom:1.5rem; }
        .alert-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
        .alert-error { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
        .info-box { background:#f0f2f5; border-radius:20px; padding:1.5rem; margin-top:1.5rem; text-align:center; }

        /* Stories Gallery */
        .stories-section { margin:3rem 0; }
        .stories-section h2 { font-size:1.8rem; margin-bottom:1.5rem; color:#e94560; text-align:center; }
        .stories-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:2rem; }
        .story-card { background:white; border-radius:20px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.08); transition:transform 0.3s; }
        .story-card:hover { transform:translateY(-5px); }
        .story-media { width:100%; height:200px; object-fit:cover; background:#e9ecef; }
        .story-content { padding:1.2rem; }
        .story-title { font-size:1.3rem; font-weight:bold; margin-bottom:0.5rem; }
        .story-meta { color:#e94560; font-size:0.8rem; margin-bottom:0.5rem; }
        .story-description { color:#555; line-height:1.5; margin-bottom:0.5rem; }
        .story-author { font-size:0.8rem; color:#777; border-top:1px solid #eee; padding-top:0.5rem; margin-top:0.5rem; }

        footer { background:#0a0a1a; color:#ccc; text-align:center; padding:2rem; margin-top:3rem; }
        @media (max-width:768px) { .form-card { padding:1.5rem; } .nav-links { margin-top:1rem; justify-content:center; width:100%; } }
    </style>
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo">Kock P <span>Talkshow</span></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="shows.php">Shows</a></li>
                <li><a href="request_talk.php">Request Talk</a></li>
                <li><a href="share_history.php" class="active">Share Story</a></li>
                <li><a href="advertise.php">Software projects</a></li>
                <li><a href="suggest_guest.php">Suggest Guest</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h1 class="page-title">📜 Share Important History / Event</h1>
    <p class="subtitle">Do you know a forgotten story, cultural event, or personal memory that deserves to be heard? Submit it here.</p>

    <?php if ($is_admin): ?>
        <!-- Admin Add Form -->
        <div class="form-card admin-section">
            <h2 style="color:#e94560; margin-bottom:1rem;">🔧 Admin: Add New History Story</h2>
            <?php if ($admin_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($admin_success); ?></div>
            <?php elseif ($admin_error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($admin_error); ?></div>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="admin_add" value="1">
                <div class="form-group">
                    <label for="admin_title">Story Title *</label>
                    <input type="text" id="admin_title" name="admin_title" required>
                </div>
                <div class="form-group">
                    <label for="admin_description">Description / Full Story *</label>
                    <textarea id="admin_description" name="admin_description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="admin_location">Location (optional)</label>
                    <input type="text" id="admin_location" name="admin_location">
                </div>
                <div class="form-group">
                    <label for="admin_event_date">Date of Event (optional)</label>
                    <input type="date" id="admin_event_date" name="admin_event_date">
                </div>
                <div class="form-group">
                    <label for="admin_media_file">Upload Image or Video (optional, max 10MB)</label>
                    <input type="file" id="admin_media_file" name="admin_media_file" accept="image/*,video/*">
                </div>
                <div class="form-group">
                    <label for="admin_submitter_name">Submitter Name *</label>
                    <input type="text" id="admin_submitter_name" name="admin_submitter_name" required>
                </div>
                <div class="form-group">
                    <label for="admin_submitter_contact">Submitter Contact (optional)</label>
                    <input type="text" id="admin_submitter_contact" name="admin_submitter_contact">
                </div>
                <div class="form-group">
                    <label for="admin_status">Status</label>
                    <select id="admin_status" name="admin_status">
                        <option value="approved">Approved (visible immediately)</option>
                        <option value="pending">Pending (needs review)</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Add Story (Admin)</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Public Submission Form -->
    <div class="form-card">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Story Title *</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description / Full Story *</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                <small>Tell us what happened, when, and why it matters.</small>
            </div>
            <div class="form-group">
                <label for="location">Location (optional)</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location ?? ''); ?>" placeholder="e.g., Addis Ababa, Gondar, Harar">
            </div>
            <div class="form-group">
                <label for="event_date">Date of Event (optional)</label>
                <input type="date" id="event_date" name="event_date" value="<?php echo htmlspecialchars($event_date ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="media_file">Upload Image or Video (optional, max 10MB)</label>
                <input type="file" id="media_file" name="media_file" accept="image/*,video/*" class="file-input">
                <small>Allowed: JPG, PNG, GIF, WEBP, MP4, WEBM, OGG</small>
            </div>
            <div class="form-group">
                <label for="submitter_name">Your Name *</label>
                <input type="text" id="submitter_name" name="submitter_name" value="<?php echo htmlspecialchars($submitter_name ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="submitter_contact">Your Email or Phone (optional, for follow-up)</label>
                <input type="text" id="submitter_contact" name="submitter_contact" value="<?php echo htmlspecialchars($submitter_contact ?? ''); ?>">
            </div>
            <button type="submit" class="btn-submit">Submit Story <i class="fas fa-history"></i></button>
        </form>

        <div class="info-box">
            <i class="fas fa-shield-alt" style="font-size: 2rem; color: #e94560;"></i>
            <p style="margin-top: 10px;">All submissions are reviewed by our editorial team for accuracy and relevance. If approved, your story may be featured on our platform or in a special episode.</p>
        </div>
    </div>

    <!-- ==================== DISPLAY APPROVED STORIES ==================== -->
    <?php if (count($stories) > 0): ?>
        <div class="stories-section">
            <h2>📖 Featured Historical Stories</h2>
            <div class="stories-grid">
                <?php foreach ($stories as $story): ?>
                    <div class="story-card">
                        <?php if (!empty($story['media_path']) && file_exists($story['media_path'])): ?>
                            <?php 
                                $ext = strtolower(pathinfo($story['media_path'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                                    <video class="story-media" controls>
                                        <source src="<?php echo htmlspecialchars($story['media_path']); ?>">
                                    </video>
                                <?php else: ?>
                                    <img class="story-media" src="<?php echo htmlspecialchars($story['media_path']); ?>" alt="<?php echo htmlspecialchars($story['title']); ?>">
                                <?php endif; ?>
                        <?php else: ?>
                            <div class="story-media" style="display:flex; align-items:center; justify-content:center; background:#e9ecef;">
                                <i class="fas fa-image" style="font-size:2.5rem; color:#adb5bd;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="story-content">
                            <div class="story-title"><?php echo htmlspecialchars($story['title']); ?></div>
                            <div class="story-meta">
                                <?php if ($story['location']): ?>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($story['location']); ?>
                                <?php endif; ?>
                                <?php if ($story['event_date']): ?>
                                    &nbsp;|&nbsp;<i class="far fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($story['event_date'])); ?>
                                <?php endif; ?>
                            </div>
                            <div class="story-description">
                                <?php echo nl2br(htmlspecialchars(substr($story['description'], 0, 200))); ?>
                                <?php if (strlen($story['description']) > 200) echo '...'; ?>
                            </div>
                            <div class="story-author">
                                <i class="fas fa-user"></i> Shared by: <?php echo htmlspecialchars($story['submitter_name']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 Kock P Talkshow – Preserving our past, inspiring our future.</p>
        <p><i class="fab fa-facebook"></i> <i class="fab fa-twitter"></i> <i class="fab fa-youtube"></i> <i class="fab fa-telegram"></i></p>
    </div>
</footer>

</body>
</html>