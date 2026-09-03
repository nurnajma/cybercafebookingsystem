<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/avatars.php';
header('Content-Type: application/json');

$user = currentUser();
if (!$user) { echo json_encode(['error' => 'Not logged in.']); exit; }

$action = $_POST['action'] ?? '';

if ($action === 'update_avatar') {
    $key     = trim($_POST['avatar_key'] ?? '');
    $avatars = getAvatars();

    if (!isset($avatars[$key])) {
        echo json_encode(['error' => 'Invalid avatar selection.']); exit;
    }

    getDB()->prepare("UPDATE users SET avatar_key = ? WHERE user_id = ?")
           ->execute([$key, $user['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Avatar updated!']);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);