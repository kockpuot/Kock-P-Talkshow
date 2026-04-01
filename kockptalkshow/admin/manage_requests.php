<?php
session_start();
require_once '../database/kock_p_talkshow.php';

// Authentication check (same as dashboard)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

// Handle status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    if (in_array($action, ['approve', 'reject'])) {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE talk_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        header('Location: manage_requests.php');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM talk_requests WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_requests.php');
    exit;
}

// Fetch all talk requests
$requests = $pdo->query("SELECT * FROM talk_requests ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Talk Requests - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;}
        .container{max-width:1200px;margin:20px auto;padding:0 20px;}
        header{background:#1a1a2e;color:white;padding:1rem;display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:1.5rem;font-weight:bold;}
        .logo span{color:#e94560;}
        table{width:100%;background:white;border-collapse:collapse;border-radius:12px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;}
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
        .back-link{display:inline-block;margin:20px 0;color:#e94560;}
        footer{text-align:center;margin-top:40px;padding:20px;background:#1a1a2e;color:white;}
    </style>
</head>
<body>
<header>
    <div class="logo">Kock P <span>Admin</span></div>
    <div><a href="dashboard.php" style="color:#e94560;">Dashboard</a> | <a href="logout.php" style="color:#e94560;">Logout</a></div>
</header>
<div class="container">
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2>Manage Talk Requests</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Contact</th><th>Topic</th><th>Type</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td><?php echo $req['id']; ?></td>
                <td><?php echo htmlspecialchars($req['name']); ?></td>
                <td><?php echo htmlspecialchars($req['contact']); ?></td>
                <td><?php echo htmlspecialchars($req['topic']); ?></td>
                <td><?php echo $req['type']; ?></td>
                <td><span class="status status-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                <td><?php echo date('Y-m-d', strtotime($req['created_at'])); ?></td>
                <td>
                    <?php if ($req['status'] == 'pending'): ?>
                        <a href="?action=approve&id=<?php echo $req['id']; ?>" class="btn btn-approve" onclick="return confirm('Approve this request?')">Approve</a>
                        <a href="?action=reject&id=<?php echo $req['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this request?')">Reject</a>
                    <?php endif; ?>
                    <a href="?delete=<?php echo $req['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete permanently?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<footer>&copy; 2025 Kock P Talkshow Admin</footer>
</body>
</html>