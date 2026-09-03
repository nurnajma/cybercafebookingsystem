<?php
require_once __DIR__ . '/includes/db.php';

$passwords = [
    'admin'          => 'admin123',
    'Adham_Yahya'    => 'password123',
    'Ammar_Bukhari'  => 'password123',
    'Shazani_Shakri' => 'password123',
];

$db = getDB();
$updated = 0;
foreach ($passwords as $username => $plain) {
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);
    $updated += $stmt->rowCount();
}

echo '<div style="font-family:monospace;padding:20px;border-radius:8px;background:' . ($updated > 0 ? '#0f0' : '#f00') . ';color:#000">';
if ($updated > 0) {
    echo "<strong>✅ $updated user password(s) reset successfully!</strong><br><br>";
    foreach ($passwords as $u => $p) echo "Username: <strong>$u</strong> → Password: <strong>$p</strong><br>";
    echo '<br><span style="color:red">⚠️ DELETE this file immediately after use!</span>';
} else {
    echo '<strong>❌ No users found. Make sure you imported database.sql first.</strong>';
}
echo '</div>';