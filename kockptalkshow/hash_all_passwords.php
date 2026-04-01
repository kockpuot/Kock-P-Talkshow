<?php
require_once 'database/kock_p_talkshow.php';
$users = $pdo->query("SELECT id, password FROM users")->fetchAll();
foreach ($users as $user) {
    // If password is not already a valid hash (does not start with $2y$)
    if (!preg_match('/^\$2y\$/', $user['password'])) {
        $newHash = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
        echo "Updated user ID {$user['id']}<br>";
    }
}
echo "Done.";
?>