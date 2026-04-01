$stmt = $pdo->query("SELECT * FROM history_submissions WHERE status = 'approved' ORDER BY created_at DESC");
$approvedStories = $stmt->fetchAll();