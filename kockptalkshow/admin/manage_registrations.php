<?php
session_start();
require_once '../database/kock_p_talkshow.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php'); exit;
}

$pendingList = $pdo->query("SELECT * FROM pending_registrations WHERE status='pending' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Manage Registrations</title><style>/* basic */</style></head>
<body>
<h1>Pending Registrations</h1>
<table border="1">
    <tr><th>ID</th><th>Title</th><th>Full Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Desired Role</th><th>Actions</th></tr>
    <?php foreach($pendingList as $p): ?>
    <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['title']) ?></td>
        <td><?= htmlspecialchars($p['fullname']) ?></td>
        <td><?= htmlspecialchars($p['username']) ?></td>
        <td><?= htmlspecialchars($p['email']) ?></td>
        <td><?= htmlspecialchars($p['phone']) ?></td>
        <td><?= $p['desired_role'] ?></td>
        <td>
            <a href="approve_registration.php?token=<?= $p['token'] ?>&action=approve">Approve</a> |
            <a href="approve_registration.php?token=<?= $p['token'] ?>&action=reject">Reject</a>
         </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>