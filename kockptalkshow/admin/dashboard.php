<?php
// admin/dashboard.php - Admin dashboard with overview stats
session_start();
require_once '../database/kock_p_talkshow.php';

// Authentication check - redirect to main login page if not admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Helper function to get count safely (if table doesn't exist, return 0)
function safeCount($pdo, $table, $status = null) {
    try {
        if ($status) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE status = ?");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        }
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0; // Table doesn't exist yet
    }
}

// Get counts for dashboard
$count_requests = safeCount($pdo, 'talk_requests', 'pending');
$count_history = safeCount($pdo, 'history_submissions', 'pending');
$count_ads = safeCount($pdo, 'ad_inquiries', 'pending');
$count_guests = safeCount($pdo, 'guest_suggestions', 'pending');

// Total counts
$total_requests = safeCount($pdo, 'talk_requests');
$total_history = safeCount($pdo, 'history_submissions');
$total_ads = safeCount($pdo, 'ad_inquiries');
$total_guests = safeCount($pdo, 'guest_suggestions');

// Get admin name from session (fallback)
$admin_name = $_SESSION['admin_user'] ?? $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        header {
            background: #1a1a2e;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .logo span {
            color: #e94560;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            text-align: center;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #e94560;
        }
        .stat-label {
            color: #666;
            margin-top: 8px;
            font-weight: 500;
        }
        .stat-sub {
            font-size: 0.85rem;
            color: #999;
            margin-top: 5px;
        }
        .section-title {
            margin: 30px 0 20px;
            font-size: 1.5rem;
            border-left: 4px solid #e94560;
            padding-left: 15px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .menu-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            background: #e94560;
            color: white;
        }
        .menu-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        .menu-card h3 {
            font-size: 1.2rem;
        }
        .menu-card p {
            font-size: 0.85rem;
            margin-top: 5px;
            opacity: 0.8;
        }
        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            background: #1a1a2e;
            color: white;
        }
        .logout-link {
            color: #e94560;
            text-decoration: none;
            font-weight: 500;
        }
        .logout-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }
            header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="logo">Kock P <span>Admin Panel</span></div>
    <div>Welcome, <?php echo htmlspecialchars($admin_name); ?> | <a href="logout.php" class="logout-link">Logout</a></div>
</header>
<div class="container">
    <h2>Dashboard Overview</h2>

    <!-- Stats Cards -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $count_requests; ?></div>
            <div class="stat-label">Pending Talk Requests</div>
            <div class="stat-sub">Total: <?php echo $total_requests; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $count_history; ?></div>
            <div class="stat-label">Pending History Stories</div>
            <div class="stat-sub">Total: <?php echo $total_history; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $count_ads; ?></div>
            <div class="stat-label">Pending Ad Inquiries</div>
            <div class="stat-sub">Total: <?php echo $total_ads; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $count_guests; ?></div>
            <div class="stat-label">Pending Guest Suggestions</div>
            <div class="stat-sub">Total: <?php echo $total_guests; ?></div>
        </div>
    </div>

    <!-- Management Links -->
    <div class="section-title">📋 Content Management</div>
    <div class="menu-grid">
        <a href="manage_requests.php" class="menu-card">
            <i class="fas fa-microphone-alt"></i>
            <h3>Talk Requests</h3>
            <p>Approve, reject or delete talk requests</p>
        </a>
        <a href="manage_history.php" class="menu-card">
            <i class="fas fa-history"></i>
            <h3>History Stories</h3>
            <p>Manage submitted historical stories</p>
        </a>
        <a href="manage_ads.php" class="menu-card">
            <i class="fas fa-chart-line"></i>
            <h3>Ad Inquiries</h3>
            <p>Review and approve advertisements</p>
        </a>
        <a href="manage_guests.php" class="menu-card">
            <i class="fas fa-users"></i>
            <h3>Guest Suggestions</h3>
            <p>Manage future guest suggestions</p>
        </a>
        <a href="manage_home.php" class="menu-card">
            <i class="fas fa-home"></i>
            <h3>Home Content</h3>
            <p>Update featured talks and latest shows</p>
        </a>
    </div>

    <!-- Quick Info -->
    <div style="background: white; padding: 20px; border-radius: 16px; margin-top: 20px;">
        <h3><i class="fas fa-info-circle"></i> Quick Tips</h3>
        <ul style="margin-left: 20px; margin-top: 10px; line-height: 1.6;">
            <li>Items with "pending" status need your review.</li>
            <li>Once approved, content may appear on the public website (depending on your frontend configuration).</li>
            <li>Use the "Delete" button with caution – it permanently removes the record and any uploaded files.</li>
            <li>Make sure all required tables exist in the database (run the SQL setup script).</li>
        </ul>
    </div>
</div>
<footer>
    &copy; 2025 Kock P Talkshow Admin – Manage your content efficiently.
</footer>
</body>
</html>