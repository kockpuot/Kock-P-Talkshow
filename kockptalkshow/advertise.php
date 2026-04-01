<?php
// advertise.php – Projects Portfolio with Admin Add Capability
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

// Determine if current user is admin (modify according to your login system)
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle new project submission (admin only)
$add_success = '';
$add_error = '';

if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    // Sanitize inputs
    $project_name = trim($_POST['project_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $developer_name = trim($_POST['developer_name'] ?? '');

    if (empty($project_name) || empty($description) || empty($developer_name)) {
        $add_error = "Project name, description, and developer name are required.";
    } else {
        // File upload: project image
        $project_image_path = null;
        if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_image = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_tmp = $_FILES['project_image']['tmp_name'];
            $file_type = mime_content_type($file_tmp);
            if (in_array($file_type, $allowed_image)) {
                $upload_dir = __DIR__ . '/uploads/projects/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['project_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('proj_', true) . '.' . $ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $project_image_path = 'uploads/projects/' . $new_filename;
                } else {
                    $add_error = "Failed to upload project image.";
                }
            } else {
                $add_error = "Project image must be JPG, PNG, GIF, or WEBP.";
            }
        }

        // File upload: developer image
        $developer_image_path = null;
        if (empty($add_error) && isset($_FILES['developer_image']) && $_FILES['developer_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_image = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_tmp = $_FILES['developer_image']['tmp_name'];
            $file_type = mime_content_type($file_tmp);
            if (in_array($file_type, $allowed_image)) {
                $upload_dir = __DIR__ . '/uploads/developers/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['developer_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('dev_', true) . '.' . $ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $developer_image_path = 'uploads/developers/' . $new_filename;
                } else {
                    $add_error = "Failed to upload developer image.";
                }
            } else {
                $add_error = "Developer image must be JPG, PNG, GIF, or WEBP.";
            }
        }

        if (empty($add_error) && $pdo) {
            try {
                $sql = "INSERT INTO projects (project_name, description, project_image, developer_name, developer_image) 
                        VALUES (:name, :desc, :proj_img, :dev_name, :dev_img)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $project_name,
                    ':desc' => $description,
                    ':proj_img' => $project_image_path,
                    ':dev_name' => $developer_name,
                    ':dev_img' => $developer_image_path
                ]);
                $add_success = "✅ Project added successfully!";
                // Optionally clear form fields by not repopulating them
            } catch (PDOException $e) {
                $add_error = "Database error: " . $e->getMessage();
            }
        } elseif (empty($add_error) && !$pdo) {
            $add_error = $db_error ?? "Database not available.";
        }
    }
}

// Fetch all projects
$projects = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developed Projects – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        .page-title { text-align:center; margin:2rem 0 0.5rem; font-size:2.5rem; }
        .subtitle { text-align:center; color:#555; margin-bottom:2rem; }
        
        /* Projects Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        .project-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        .project-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #e9ecef;
        }
        .project-info {
            padding: 1.2rem;
        }
        .project-name {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #1a1a2e;
        }
        .project-description {
            color: #555;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        .developer-section {
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid #eee;
            padding-top: 1rem;
            margin-top: 0.5rem;
        }
        .developer-photo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e94560;
        }
        .developer-details {
            flex: 1;
        }
        .developer-label {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .developer-name {
            font-weight: 600;
            color: #333;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: #fff;
            border-radius: 20px;
            margin: 2rem 0;
        }
        
        /* Admin Form Styles */
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
        
        footer { background:#0a0a1a; color:#ccc; text-align:center; padding:2rem; margin-top:3rem; }
        .social-links a { color:#ccc; margin:0 10px; font-size:1.5rem; transition:0.3s; display:inline-block; }
        .social-links a:hover { color:#e94560; }
        @media (max-width:768px) {
            .projects-grid { grid-template-columns: 1fr; }
            .nav-links { margin-top:1rem; justify-content:center; width:100%; }
            .logo { font-size:1.2rem; }
            .logo img { height:35px; }
        }
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
                <li><a href="advertise.php" class="active">Projects</a></li>
                <li><a href="suggest_guest.php">Suggest Guest</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h1 class="page-title">🚀 Developed Projects</h1>
    <p class="subtitle">Check out the apps, websites, and tools we've built</p>

    <!-- Admin Add Project Form (visible only to admin) -->
    <?php if ($is_admin): ?>
        <div class="admin-section">
            <h2><i class="fas fa-plus-circle"></i> Add New Project</h2>
            <?php if ($add_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($add_success); ?></div>
            <?php elseif ($add_error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($add_error); ?></div>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="project_name">Project Name *</label>
                    <input type="text" id="project_name" name="project_name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="project_image">Project Image (optional)</label>
                    <input type="file" id="project_image" name="project_image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small>Allowed: JPG, PNG, GIF, WEBP</small>
                </div>
                <div class="form-group">
                    <label for="developer_name">Developer Name *</label>
                    <input type="text" id="developer_name" name="developer_name" required>
                </div>
                <div class="form-group">
                    <label for="developer_image">Developer Profile Image (optional, small circle)</label>
                    <input type="file" id="developer_image" name="developer_image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small>Allowed: JPG, PNG, GIF, WEBP</small>
                </div>
                <button type="submit" name="add_project" class="btn-submit">Add Project</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Display Projects -->
    <?php if (empty($projects) && $pdo): ?>
        <div class="empty-state">
            <i class="fas fa-code" style="font-size: 3rem; color: #e94560; margin-bottom: 1rem;"></i>
            <h3>No projects added yet</h3>
            <p><?php echo $is_admin ? 'Use the form above to add your first project.' : 'Check back soon for new projects!'; ?></p>
        </div>
    <?php elseif (!$pdo): ?>
        <div class="empty-state">
            <i class="fas fa-database" style="font-size: 3rem; color: #e94560;"></i>
            <h3>Database connection error</h3>
            <p><?php echo htmlspecialchars($db_error ?? 'Please check your configuration.'); ?></p>
        </div>
    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($projects as $project): ?>
                <div class="project-card">
                    <?php if (!empty($project['project_image']) && file_exists($project['project_image'])): ?>
                        <img class="project-image" src="<?php echo htmlspecialchars($project['project_image']); ?>" alt="<?php echo htmlspecialchars($project['project_name']); ?>">
                    <?php else: ?>
                        <div class="project-image" style="display: flex; align-items: center; justify-content: center; background: #e9ecef;">
                            <i class="fas fa-image" style="font-size: 2.5rem; color: #adb5bd;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="project-info">
                        <h2 class="project-name"><?php echo htmlspecialchars($project['project_name']); ?></h2>
                        <p class="project-description"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                        
                        <div class="developer-section">
                            <?php if (!empty($project['developer_image']) && file_exists($project['developer_image'])): ?>
                                <img class="developer-photo" src="<?php echo htmlspecialchars($project['developer_image']); ?>" alt="<?php echo htmlspecialchars($project['developer_name']); ?>">
                            <?php else: ?>
                                <div class="developer-photo" style="background: #e94560; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="developer-details">
                                <div class="developer-label">Developed by</div>
                                <div class="developer-name"><?php echo htmlspecialchars($project['developer_name']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 Kock P Talkshow – Showcasing our work and collaborations.</p>
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