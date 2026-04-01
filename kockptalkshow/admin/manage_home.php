<?php
session_start();
require_once '../database/kock_p_talkshow.php';

// Simple admin check (you already have admin login)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO home_shows (title, description, embed_url, image_url, category, is_featured, display_order) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['title'], $_POST['description'], $_POST['embed_url'], $_POST['image_url'], $_POST['category'], $_POST['is_featured'], $_POST['display_order']]);
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE home_shows SET title=?, description=?, embed_url=?, image_url=?, category=?, is_featured=?, display_order=? WHERE id=?");
        $stmt->execute([$_POST['title'], $_POST['description'], $_POST['embed_url'], $_POST['image_url'], $_POST['category'], $_POST['is_featured'], $_POST['display_order'], $_POST['id']]);
    } elseif (isset($_GET['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM home_shows WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        header('Location: manage_home.php');
        exit;
    }
    header('Location: manage_home.php');
    exit;
}

$shows = $pdo->query("SELECT * FROM home_shows ORDER BY display_order, created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Manage Home Content</title><style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;} form{margin-bottom:20px;}</style></head>
<body>
<h1>Manage Home Page Shows</h1>
<form method="post">
    <h3>Add New Show</h3>
    <input type="text" name="title" placeholder="Title" required><br>
    <textarea name="description" placeholder="Description" required></textarea><br>
    <input type="url" name="embed_url" placeholder="YouTube Embed URL" required><br>
    <input type="url" name="image_url" placeholder="Image URL (optional)"><br>
    <select name="category">
        <option value="interview">Interview</option><option value="podcast">Podcast</option><option value="history">History</option><option value="special">Special</option>
    </select><br>
    <label><input type="checkbox" name="is_featured" value="1"> Feature as Talk of the Week</label><br>
    <input type="number" name="display_order" placeholder="Display Order (0 first)" value="0"><br>
    <button type="submit" name="add">Add Show</button>
</form>
<hr>
<table>
    <tr><th>ID</th><th>Title</th><th>Category</th><th>Featured</th><th>Order</th><th>Actions</th></tr>
    <?php foreach ($shows as $show): ?>
    <tr>
        <td><?= $show['id'] ?></td>
        <td><?= htmlspecialchars($show['title']) ?></td>
        <td><?= $show['category'] ?></td>
        <td><?= $show['is_featured'] ? 'Yes' : 'No' ?></td>
        <td><?= $show['display_order'] ?></td>
        <td>
            <a href="?delete=<?= $show['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
            <!-- You can add inline edit form here, but for brevity, use phpMyAdmin or extend -->
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>