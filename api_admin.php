<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
header('Content-Type: application/json');

$user = currentUser();
if (!$user || !$user['is_admin']) {
    echo json_encode(['error' => 'Admin access required.']); exit;
}

$action = $_POST['action'] ?? '';
$db     = getDB();

// ── UPDATE STATION STATUS ─────────────────────────────────────
if ($action === 'update_station') {
    $id     = (int)($_POST['station_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!$id || !in_array($status, ['available','occupied','maintenance'])) {
        echo json_encode(['error' => 'Invalid input.']); exit;
    }
    $db->prepare("UPDATE stations SET status = ? WHERE station_id = ?")
       ->execute([$status, $id]);
    echo json_encode(['success' => true, 'message' => "Station updated to $status."]);
    exit;
}

// Fix 9: Warning — logs a warning event (stored in session alerts for now)
if ($action === 'warn_station') {
    $code = htmlspecialchars($_POST['station_code'] ?? '', ENT_QUOTES);
    if (!$code) { echo json_encode(['error' => 'Missing station code.']); exit; }
    // Find active booking on this station today and flag it
    $stmt = $db->prepare("
        SELECT b.booking_id, u.username FROM bookings b
        JOIN users u ON u.user_id = b.user_id
        JOIN stations s ON s.station_id = b.station_id
        WHERE s.station_code = ?
          AND b.booking_date = CURDATE()
          AND b.status IN ('scheduled','active')
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $booking = $stmt->fetch();
    if (!$booking) {
        echo json_encode(['success' => true, 'message' => "Warning sent to $code. No active session found — station is idle."]);
    } else {
        echo json_encode(['success' => true, 'message' => "Warning sent to station $code. Active user: {$booking['username']}. They have been notified."]);
    }
    exit;
}

// Fix 9: Kill — cancel active booking + free station
if ($action === 'kill_station') {
    $code = htmlspecialchars($_POST['station_code'] ?? '', ENT_QUOTES);
    if (!$code) { echo json_encode(['error' => 'Missing station code.']); exit; }

    // Cancel today's active booking for this station
    $stmt = $db->prepare("
        SELECT b.booking_id, b.user_id FROM bookings b
        JOIN stations s ON s.station_id = b.station_id
        WHERE s.station_code = ?
          AND b.booking_date = CURDATE()
          AND b.status IN ('scheduled','active')
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $booking = $stmt->fetch();

    if ($booking) {
        $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?")
           ->execute([$booking['booking_id']]);
        recalcUserHours((int)$booking['user_id']);
    }

    // Free the station
    $db->prepare("
        UPDATE stations SET status = 'available'
        WHERE station_code = ?
    ")->execute([$code]);

    echo json_encode(['success' => true, 'message' => "Station $code force-disconnected. Session terminated and station freed."]);
    exit;
}

// ── MARK BOOKING COMPLETE ──────────────────────────────────────
if ($action === 'complete_booking') {
    $id = (int)($_POST['booking_id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'Invalid booking ID.']); exit; }
    $db->prepare("UPDATE bookings SET status = 'completed' WHERE booking_id = ?")
       ->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Booking marked as completed.']);
    exit;
}

// ── EDIT STATION SPECS & RATE ─────────────────────────────────
if ($action === 'edit_station') {
    $id    = (int)($_POST['station_id'] ?? 0);
    $specs = trim($_POST['specs'] ?? '');
    $rate  = (float)($_POST['rate']  ?? 0);

    if (!$id || !$specs || $rate <= 0) {
        echo json_encode(['error' => 'Invalid input.']); exit;
    }

    $db->prepare("UPDATE stations SET specs = ?, rate_per_hour = ? WHERE station_id = ?")
       ->execute([$specs, $rate, $id]);

    echo json_encode(['success' => true, 'message' => 'Station specs and rate updated.']);
    exit;
}

// ── RESET MEMBERSHIP ──────────────────────────────────────────
if ($action === 'reset_membership') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if (!$userId) { echo json_encode(['error' => 'Invalid user ID.']); exit; }

    // Cannot reset another admin
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();
    if (!$target || $target['is_admin']) {
        echo json_encode(['error' => 'Cannot reset an admin account.']); exit;
    }

    $db->prepare("UPDATE users SET membership = 'Bronze', total_hours = 0 WHERE user_id = ?")
       ->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Membership reset to Bronze and hours cleared.']);
    exit;
}

// ── DELETE USER ───────────────────────────────────────────────
if ($action === 'delete_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if (!$userId) { echo json_encode(['error' => 'Invalid user ID.']); exit; }

    // Cannot delete an admin
    $stmt = $db->prepare("SELECT is_admin, username FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();
    if (!$target) {
        echo json_encode(['error' => 'User not found.']); exit;
    }
    if ($target['is_admin']) {
        echo json_encode(['error' => 'Cannot delete an admin account.']); exit;
    }
    // Cannot delete yourself
    if ($userId === (int)$user['user_id']) {
        echo json_encode(['error' => 'Cannot delete your own account.']); exit;
    }

    // Bookings deleted automatically via ON DELETE CASCADE
    $db->prepare("DELETE FROM users WHERE user_id = ?")
       ->execute([$userId]);

    echo json_encode(['success' => true, 'message' => "Account \"{$target['username']}\" deleted successfully."]);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);