<?php
// my_account.php - User dashboard with admin actions on submissions + admin add forms
session_start();
require_once 'database/kock_p_talkshow.php';

// Check login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'user';
$user_email = $_SESSION['user_email'] ?? '';

// Check if 'status' column exists in home_shows
try {
    $pdo->query("SELECT status FROM home_shows LIMIT 1");
    $hasStatus = true;
} catch (PDOException $e) {
    $hasStatus = false;
}

// --- Helper functions for admin actions (approve/reject/hide) ---
$message = '';
$error = '';

if ($role === 'admin' && isset($_GET['action']) && isset($_GET['type']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $type = $_GET['type'];
    $id = (int)$_GET['id'];
    $allowedActions = ['approve', 'reject', 'hide'];
    if (in_array($action, $allowedActions)) {
        $newStatus = '';
        if ($action === 'approve') $newStatus = 'approved';
        elseif ($action === 'reject') $newStatus = 'rejected';
        elseif ($action === 'hide') $newStatus = 'hidden';
        
        $table = '';
        if ($type === 'talk') $table = 'talk_requests';
        elseif ($type === 'history') $table = 'history_submissions';
        elseif ($type === 'guest') $table = 'guest_suggestions';
        
        if ($table) {
            try {
                $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $message = "✅ $type request #$id has been $action d.";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    // Redirect to remove GET parameters (avoid re-submission on refresh)
    header("Location: my_account.php?msg=" . urlencode($message));
    exit;
}

// --- Handle admin adding new records ---
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Talk Request
    if (isset($_POST['add_talk'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $topic = trim($_POST['topic']);
        $type = $_POST['type'];
        $status = $_POST['status'];
        try {
            $stmt = $pdo->prepare("INSERT INTO talk_requests (name, email, phone, topic, type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $phone, $topic, $type, $status]);
            $message = "✅ Talk request added.";
        } catch (PDOException $e) {
            $error = "DB error: " . $e->getMessage();
        }
    }
    // Add History Story
    elseif (isset($_POST['add_history'])) {
        $title = trim($_POST['title']);
        $story_text = trim($_POST['story_text']);
        $submitter_name = trim($_POST['submitter_name']);
        $status = $_POST['status'];
        try {
            $stmt = $pdo->prepare("INSERT INTO history_submissions (title, story_text, submitter_name, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $story_text, $submitter_name, $status]);
            $message = "✅ History story added.";
        } catch (PDOException $e) {
            $error = "DB error: " . $e->getMessage();
        }
    }
    // Add Guest Suggestion
    elseif (isset($_POST['add_guest'])) {
        $guest_name = trim($_POST['guest_name']);
        $profession = trim($_POST['profession']);
        $reason = trim($_POST['reason']);
        $suggested_by = trim($_POST['suggested_by']);
        $status = $_POST['status'];
        $guest_image = null;
        // Handle image upload
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
                    $error = "Failed to upload guest image.";
                }
            } else {
                $error = "Invalid image type. Allowed: JPG, PNG, GIF, WEBP.";
            }
        }
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO guest_suggestions (guest_name, profession, reason, suggested_by, guest_image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$guest_name, $profession, $reason, $suggested_by, $guest_image, $status]);
                $message = "✅ Guest suggestion added.";
            } catch (PDOException $e) {
                $error = "DB error: " . $e->getMessage();
            }
        }
    }
    // Add Project
    elseif (isset($_POST['add_project'])) {
        $project_name = trim($_POST['project_name']);
        $description = trim($_POST['description']);
        $developer_name = trim($_POST['developer_name']);
        $project_image = null;
        $developer_image = null;
        // Upload project image
        if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tmp = $_FILES['project_image']['tmp_name'];
            $type = mime_content_type($tmp);
            if (in_array($type, $allowed)) {
                $upload_dir = __DIR__ . '/uploads/projects/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['project_image']['name'], PATHINFO_EXTENSION);
                $new_name = uniqid('proj_', true) . '.' . $ext;
                if (move_uploaded_file($tmp, $upload_dir . $new_name)) {
                    $project_image = 'uploads/projects/' . $new_name;
                } else {
                    $error = "Failed to upload project image.";
                }
            } else {
                $error = "Project image must be JPG, PNG, GIF, WEBP.";
            }
        }
        // Upload developer image
        if (empty($error) && isset($_FILES['developer_image']) && $_FILES['developer_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $tmp = $_FILES['developer_image']['tmp_name'];
            $type = mime_content_type($tmp);
            if (in_array($type, $allowed)) {
                $upload_dir = __DIR__ . '/uploads/developers/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['developer_image']['name'], PATHINFO_EXTENSION);
                $new_name = uniqid('dev_', true) . '.' . $ext;
                if (move_uploaded_file($tmp, $upload_dir . $new_name)) {
                    $developer_image = 'uploads/developers/' . $new_name;
                } else {
                    $error = "Failed to upload developer image.";
                }
            } else {
                $error = "Developer image must be JPG, PNG, GIF, WEBP.";
            }
        }
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO projects (project_name, description, project_image, developer_name, developer_image) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$project_name, $description, $project_image, $developer_name, $developer_image]);
                $message = "✅ Project added.";
            } catch (PDOException $e) {
                $error = "DB error: " . $e->getMessage();
            }
        }
    }
    // Delete project (optional)
    elseif (isset($_GET['delete_project'])) {
        $id = (int)$_GET['delete_project'];
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $message = "🗑️ Project deleted.";
        header("Location: my_account.php?msg=" . urlencode($message));
        exit;
    }
    
    // After any POST, redirect to clear form resubmission
    if (!isset($_GET['delete_project'])) {
        header("Location: my_account.php?msg=" . urlencode($message) . "&err=" . urlencode($error));
        exit;
    }
}

// Show message from redirect
if (isset($_GET['msg'])) $message = $_GET['msg'];
if (isset($_GET['err'])) $error = $_GET['err'];

// --- Fetch data for display ---
if ($role === 'admin') {
    // Admin sees all records
    $talkReqs = $pdo->query("SELECT * FROM talk_requests ORDER BY created_at DESC")->fetchAll();
    $historyStories = $pdo->query("SELECT * FROM history_submissions ORDER BY created_at DESC")->fetchAll();
    $guestSuggestions = $pdo->query("SELECT * FROM guest_suggestions ORDER BY created_at DESC")->fetchAll();
    $allProjects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
} else {
    // Regular users see only their own
    $talkReqs = $pdo->prepare("SELECT * FROM talk_requests WHERE name = ? OR email = ? ORDER BY created_at DESC");
    $talkReqs->execute([$username, $user_email]);
    $talkReqs = $talkReqs->fetchAll();
    
    $historyStories = $pdo->prepare("SELECT * FROM history_submissions WHERE submitter_name = ? ORDER BY created_at DESC");
    $historyStories->execute([$username]);
    $historyStories = $historyStories->fetchAll();
    
    $guestSuggestions = $pdo->prepare("SELECT * FROM guest_suggestions WHERE suggested_by = ? ORDER BY created_at DESC");
    $guestSuggestions->execute([$username]);
    $guestSuggestions = $guestSuggestions->fetchAll();
    
    $allProjects = []; // Regular users don't see projects table
}

// Shows management (same as before)
$allShows = [];
if (in_array($role, ['admin', 'editor'])) {
    $allShows = $pdo->query("SELECT * FROM home_shows ORDER BY display_order, created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', sans-serif; background:#f0f2f5; color:#222; }
        .container { max-width:1400px; margin:auto; padding:0 20px; }
        header { background:#1a1a2e; color:white; padding:1rem 0; position:sticky; top:0; z-index:100; }
        nav { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .logo { display:flex; align-items:center; gap:10px; font-size:1.8rem; font-weight:bold; }
        .logo img { height:50px; width:auto; }
        .logo span { color:#e94560; }
        .nav-links { display:flex; gap:1.5rem; list-style:none; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; }
        .nav-links a:hover { color:#e94560; }
        .dashboard { background:white; border-radius:24px; padding:2rem; margin:2rem 0; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
        .section-title { font-size:1.5rem; margin:1.5rem 0 1rem; color:#e94560; border-bottom:2px solid #e94560; display:inline-block; }
        table { width:100%; border-collapse:collapse; margin:1rem 0; background:white; }
        th, td { border:1px solid #ddd; padding:10px; text-align:left; vertical-align:middle; }
        th { background:#e94560; color:white; }
        .btn { background:#e94560; color:white; padding:6px 12px; border-radius:30px; text-decoration:none; display:inline-block; border:none; cursor:pointer; font-size:0.8rem; }
        .btn-sm { padding:4px 10px; font-size:0.75rem; margin:0 2px; }
        .btn-secondary { background:#6c757d; }
        .btn-success { background:#28a745; }
        .btn-warning { background:#ffc107; color:#333; }
        .btn-danger { background:#dc3545; }
        .status-badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:0.7rem; }
        .status-approved { background:#28a745; color:white; }
        .status-pending { background:#ffc107; color:#333; }
        .status-rejected { background:#dc3545; color:white; }
        .status-hidden { background:#6c757d; color:white; }
        .form-group { margin-bottom:1rem; }
        label { display:block; font-weight:600; margin-bottom:0.3rem; }
        input, select, textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:12px; }
        .admin-forms { background:#f9f9f9; padding:1rem; border-radius:16px; margin-bottom:2rem; }
        .admin-forms h3 { margin-top:1rem; margin-bottom:0.5rem; }
        footer { background:#0a0a1a; color:#ccc; text-align:center; padding:2rem; margin-top:3rem; }
        .social-links a { color:#ccc; margin:0 10px; font-size:1.5rem; display:inline-block; }
        .social-links a:hover { color:#e94560; }
        @media (max-width:768px) { table { font-size:12px; } .nav-links { margin-top:1rem; justify-content:center; width:100%; } .logo { font-size:1.2rem; } .logo img { height:35px; } }
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
                <li><a href="my_account.php" class="active">My Account</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>
<div class="container">
    <div class="dashboard">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>Role: <strong><?php echo ucfirst($role); ?></strong></p>

        <?php if ($message): ?>
            <div style="background:#d4edda; padding:10px; border-radius:12px; margin-bottom:1rem;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background:#f8d7da; padding:10px; border-radius:12px; margin-bottom:1rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- ========== TALK REQUESTS TABLE ========== -->
        <div class="section-title">📋 Talk Requests</div>
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Topic</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if(count($talkReqs)): ?>
                    <?php foreach($talkReqs as $req): ?>
                        <tr>
                            <td><?php echo $req['id']; ?></td>
                            <td><?php echo htmlspecialchars($req['name']); ?></td>
                            <td><?php echo htmlspecialchars($req['topic']); ?></td>
                            <td><?php echo ucfirst($req['type']); ?></td>
                            <td><span class="status-badge status-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($req['created_at'])); ?></td>
                            <td>
                                <?php if ($role === 'admin'): ?>
                                    <a href="?action=approve&type=talk&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this talk request?')">Approve</a>
                                    <a href="?action=reject&type=talk&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this talk request?')">Reject</a>
                                    <a href="?action=hide&type=talk&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Hide this talk request?')">Hide</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No talk requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ========== HISTORY STORIES TABLE ========== -->
        <div class="section-title">📜 History Stories</div>
        <table>
            <thead><tr><th>ID</th><th>Title</th><th>Submitter</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if(count($historyStories)): ?>
                    <?php foreach($historyStories as $story): ?>
                        <tr>
                            <td><?php echo $story['id']; ?></td>
                            <td><?php echo htmlspecialchars($story['title']); ?></td>
                            <td><?php echo htmlspecialchars($story['submitter_name']); ?></td>
                            <td><span class="status-badge status-<?php echo $story['status']; ?>"><?php echo ucfirst($story['status']); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($story['created_at'])); ?></td>
                            <td>
                                <?php if ($role === 'admin'): ?>
                                    <a href="?action=approve&type=history&id=<?php echo $story['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this story?')">Approve</a>
                                    <a href="?action=reject&type=history&id=<?php echo $story['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this story?')">Reject</a>
                                    <a href="?action=hide&type=history&id=<?php echo $story['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Hide this story?')">Hide</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No history stories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ========== GUEST SUGGESTIONS TABLE ========== -->
        <div class="section-title">👥 Guest Suggestions</div>
        <table>
            <thead><tr><th>ID</th><th>Guest Name</th><th>Profession</th><th>Suggested By</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if(count($guestSuggestions)): ?>
                    <?php foreach($guestSuggestions as $guest): ?>
                        <tr>
                            <td><?php echo $guest['id']; ?></td>
                            <td><?php echo htmlspecialchars($guest['guest_name']); ?></td>
                            <td><?php echo htmlspecialchars($guest['profession']); ?></td>
                            <td><?php echo htmlspecialchars($guest['suggested_by']); ?></td>
                            <td><span class="status-badge status-<?php echo $guest['status']; ?>"><?php echo ucfirst($guest['status']); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($guest['created_at'])); ?></td>
                            <td>
                                <?php if ($role === 'admin'): ?>
                                    <a href="?action=approve&type=guest&id=<?php echo $guest['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this guest?')">Approve</a>
                                    <a href="?action=reject&type=guest&id=<?php echo $guest['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this guest?')">Reject</a>
                                    <a href="?action=hide&type=guest&id=<?php echo $guest['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Hide this guest?')">Hide</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No guest suggestions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ========== PROJECTS SECTION (Admin only) ========== -->
        <?php if ($role === 'admin'): ?>
            <div class="section-title">🚀 Projects</div>
            <!-- Display existing projects -->
            <table>
                <thead><tr><th>ID</th><th>Project Name</th><th>Developer</th><th>Description</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if(count($allProjects)): ?>
                        <?php foreach($allProjects as $proj): ?>
                            <tr>
                                <td><?php echo $proj['id']; ?></td>
                                <td><?php echo htmlspecialchars($proj['project_name']); ?></td>
                                <td><?php echo htmlspecialchars($proj['developer_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($proj['description'], 0, 100)); ?>…</td>
                                <td>
                                    <a href="?delete_project=<?php echo $proj['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this project?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No projects added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- ========== ADMIN ADD FORMS ========== -->
        <?php if ($role === 'admin'): ?>
            <div class="admin-forms">
                <h2>➕ Admin Quick Add</h2>
                
                <!-- Add Talk Request -->
                <h3>📞 Add Talk Request</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group"><label>Name *</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                    <div class="form-group"><label>Topic *</label><input type="text" name="topic" required></div>
                    <div class="form-group"><label>Type</label><select name="type"><option value="interview">Interview</option><option value="podcast">Podcast</option><option value="special">Special</option></select></div>
                    <div class="form-group"><label>Status</label><select name="status"><option value="approved">Approved</option><option value="pending">Pending</option><option value="hidden">Hidden</option></select></div>
                    <button type="submit" name="add_talk" class="btn">Add Talk Request</button>
                </form>
                
                <!-- Add History Story -->
                <h3>📖 Add History Story</h3>
                <form method="post">
                    <div class="form-group"><label>Title *</label><input type="text" name="title" required></div>
                    <div class="form-group"><label>Story *</label><textarea name="story_text" rows="3" required></textarea></div>
                    <div class="form-group"><label>Submitter Name *</label><input type="text" name="submitter_name" required></div>
                    <div class="form-group"><label>Status</label><select name="status"><option value="approved">Approved</option><option value="pending">Pending</option><option value="hidden">Hidden</option></select></div>
                    <button type="submit" name="add_history" class="btn">Add History Story</button>
                </form>
                
                <!-- Add Guest Suggestion -->
                <h3>🎤 Add Guest Suggestion</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group"><label>Guest Name *</label><input type="text" name="guest_name" required></div>
                    <div class="form-group"><label>Profession *</label><input type="text" name="profession" required></div>
                    <div class="form-group"><label>Reason *</label><textarea name="reason" rows="2" required></textarea></div>
                    <div class="form-group"><label>Suggested By *</label><input type="text" name="suggested_by" required></div>
                    <div class="form-group"><label>Guest Image</label><input type="file" name="guest_image" accept="image/*"></div>
                    <div class="form-group"><label>Status</label><select name="status"><option value="approved">Approved</option><option value="pending">Pending</option><option value="hidden">Hidden</option></select></div>
                    <button type="submit" name="add_guest" class="btn">Add Guest Suggestion</button>
                </form>
                
                <!-- Add Project -->
                <h3>🛠️ Add New Project</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group"><label>Project Name *</label><input type="text" name="project_name" required></div>
                    <div class="form-group"><label>Description *</label><textarea name="description" rows="3" required></textarea></div>
                    <div class="form-group"><label>Developer Name *</label><input type="text" name="developer_name" required></div>
                    <div class="form-group"><label>Project Image (optional)</label><input type="file" name="project_image" accept="image/*"></div>
                    <div class="form-group"><label>Developer Image (optional, small circle)</label><input type="file" name="developer_image" accept="image/*"></div>
                    <button type="submit" name="add_project" class="btn">Add Project</button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- ========== MANAGE SHOWS (Admin/Editor only) ========== -->
        <?php if (in_array($role, ['admin', 'editor'])): ?>
            <div class="section-title">🎬 Manage Shows</div>
            <form method="post" style="background:#f9f9f9; padding:1rem; border-radius:16px; margin-bottom:2rem;">
                <h3>Add New Show</h3>
                <div class="form-group"><label>Title *</label><input type="text" name="title" required></div>
                <div class="form-group"><label>Description *</label><textarea name="description" required></textarea></div>
                <div class="form-group"><label>Embed URL *</label><input type="url" name="embed_url" placeholder="https://www.youtube.com/embed/..."></div>
                <div class="form-group"><label>Image URL (optional)</label><input type="url" name="image_url"></div>
                <div class="form-group"><label>Category *</label>
                    <select name="category">
                        <option value="interview">Interview</option><option value="podcast">Podcast</option>
                        <option value="special">Special Talk</option><option value="history">History Story</option>
                    </select>
                </div>
                <div class="form-group"><label>Display Order</label><input type="number" name="display_order" value="0"></div>
                <?php if ($hasStatus): ?>
                <div class="form-group"><label>Status</label>
                    <select name="status"><option value="approved">Approved</option><option value="pending">Pending</option><option value="hidden">Hidden</option></select>
                </div>
                <?php endif; ?>
                <button type="submit" name="add_show" class="btn">Add Show</button>
            </form>

            <!-- Shows Table -->
            <table>
                <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($allShows as $show): ?>
                    <tr>
                        <td><?php echo $show['id']; ?></td>
                        <td><?php echo htmlspecialchars($show['title']); ?></td>
                        <td><?php echo ucfirst($show['category']); ?></td>
                        <td><?php if($hasStatus): ?><span class="status-badge status-<?php echo $show['status']; ?>"><?php echo ucfirst($show['status']); ?></span><?php else: ?>—<?php endif; ?></td>
                        <td><?php echo $show['display_order']; ?></td>
                        <td>
                            <?php if ($hasStatus): ?>
                                <a href="?toggle_status=<?php echo $show['id']; ?>" class="btn btn-sm btn-secondary"><?php echo ($show['status']=='hidden') ? 'Unhide' : 'Hide'; ?></a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $show['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Delete permanently?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
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