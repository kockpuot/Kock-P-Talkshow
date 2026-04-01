<?php
// admin/manage_history.php - Manage history story submissions
session_start();
require_once '../database/kock_p_talkshow.php';

// Authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

// Handle status update (approve/reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    if (in_array($action, ['approve', 'reject'])) {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE history_submissions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        header('Location: manage_history.php');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Optionally delete the media file from server
    $stmt = $pdo->prepare("SELECT media_path FROM history_submissions WHERE id = ?");
    $stmt->execute([$id]);
    $media = $stmt->fetch();
    if ($media && !empty($media['media_path']) && file_exists('../' . $media['media_path'])) {
        unlink('../' . $media['media_path']); // delete file
    }
    $stmt = $pdo->prepare("DELETE FROM history_submissions WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_history.php');
    exit;
}

// Fetch all history submissions (ordered newest first)
$history = $pdo->query("SELECT * FROM history_submissions ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage History Stories - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;}
        .container{max-width:1400px;margin:20px auto;padding:0 20px;}
        header{background:#1a1a2e;color:white;padding:1rem;display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:1.5rem;font-weight:bold;}
        .logo span{color:#e94560;}
        table{width:100%;background:white;border-collapse:collapse;border-radius:12px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;vertical-align:top;}
        th{background:#e94560;color:white;}
        tr:hover{background:#f5f5f5;}
        .status{padding:4px 8px;border-radius:20px;font-size:0.8rem;}
        .status-pending{background:#ffc107;color:#333;}
        .status-approved{background:#28a745;color:white;}
        .status-rejected{background:#dc3545;color:white;}
        .btn{display:inline-block;padding:4px 10px;border-radius:5px;text-decoration:none;font-size:0.8rem;margin:0 2px;}
        .btn-approve{background:#28a745;color:white;}
        .btn-reject{background:#dc3545;color:white;}
        .btn-delete{background:#6c757d;color:white;}
        .media-preview{max-width:100px;max-height:80px;border-radius:8px;object-fit:cover;}
        .back-link{display:inline-block;margin:20px 0;color:#e94560;}
        footer{text-align:center;margin-top:40px;padding:20px;background:#1a1a2e;color:white;}
        @media (max-width:768px){th,td{font-size:12px;padding:8px;}}
    </style>
</head>
<body>
<header>
    <div class="logo">Kock P <span>Admin</span></div>
    <div><a href="dashboard.php" style="color:#e94560;">Dashboard</a> | <a href="logout.php" style="color:#e94560;">Logout</a></div>
</header>
<div class="container">
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2>📜 Manage History Stories</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Title</th><th>Description</th><th>Location</th><th>Event Date</th><th>Media</th><th>Submitted By</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($history as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo htmlspecialchars(substr($item['title'], 0, 40)); ?><?php echo strlen($item['title']) > 40 ? '...' : ''; ?></td>
                <td><?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?><?php echo strlen($item['description']) > 60 ? '...' : ''; ?></td>
                <td><?php echo htmlspecialchars($item['location'] ?? '-'); ?></td>
                <td><?php echo $item['event_date'] ? date('Y-m-d', strtotime($item['event_date'])) : '-'; ?></td>
                <td>
                    <?php if ($item['media_path'] && file_exists('../' . $item['media_path'])): ?>
                        <?php 
                            $ext = strtolower(pathinfo($item['media_path'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['mp4', 'webm', 'ogg'])):
                        ?>
                            <video class="media-preview" controls style="max-width:100px;">
                                <source src="../<?php echo $item['media_path']; ?>">
                            </video>
                        <?php else: ?>
                            <img class="media-preview" src="../<?php echo $item['media_path']; ?>" alt="media">
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#999;">No media</span>
                    <?php endif; ?>
                 </div>
                <td><?php echo htmlspecialchars($item['submitter_name']); ?></td>
                <td><span class="status status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                <td>
                    <?php if ($item['status'] == 'pending'): ?>
                        <a href="?action=approve&id=<?php echo $item['id']; ?>" class="btn btn-approve" onclick="return confirm('Approve this story? It will appear on the website.')">Approve</a>
                        <a href="?action=reject&id=<?php echo $item['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this story?')">Reject</a>
                    <?php endif; ?>
                    <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete permanently? This cannot be undone.')">Delete</a>
                 </div>
            </tr>
            <?php endforeach; ?>
            <?php if (count($history) == 0): ?>
                <tr><td colspan="10" style="text-align:center;">No history submissions found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<footer>&copy; 2025 Kock P Talkshow Admin</footer>
</body>
</html>