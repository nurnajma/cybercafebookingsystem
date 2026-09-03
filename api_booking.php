<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
header('Content-Type: application/json');

$user = currentUser();
if (!$user) { echo json_encode(['error' => 'Session expired. Please log in again.']); exit; }

$action = $_POST['action'] ?? '';
$db     = getDB();

// ── CREATE BOOKING ────────────────────────────────────────────
if ($action === 'create') {
    $stationId = (int)($_POST['station_id'] ?? 0);
    $date      = $_POST['date']       ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $duration  = (float)($_POST['duration'] ?? 0);

    if (!$stationId || !$date || !$startTime || !$duration) {
        echo json_encode(['error' => 'All fields are required.']); exit;
    }
    if ($date < date('Y-m-d')) {
        echo json_encode(['error' => 'Cannot book a date in the past.']); exit;
    }
    if ($duration <= 0 || $duration > 12) {
        echo json_encode(['error' => 'Duration must be between 1 and 12 hours.']); exit;
    }

    // Compute end time
    $startDT = new DateTime($date . ' ' . $startTime);
    $endDT   = clone $startDT;
    $endDT->modify("+{$duration} hours");
    $endTime = $endDT->format('H:i:s');

    // Check station is available
    $stmt = $db->prepare("SELECT * FROM stations WHERE station_id = ? AND status = 'available'");
    $stmt->execute([$stationId]);
    $station = $stmt->fetch();
    if (!$station) {
        echo json_encode(['error' => 'Station is no longer available.']); exit;
    }

    // Fix 4: proper overlap check — overlaps if start < other_end AND end > other_start
    $stmt = $db->prepare("
        SELECT booking_id FROM bookings
        WHERE station_id   = ?
          AND booking_date  = ?
          AND status NOT IN ('cancelled','completed')
          AND start_time    < ?
          AND end_time      > ?
    ");
    $stmt->execute([$stationId, $date, $endTime, $startTime]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Time slot conflict! Another booking overlaps this window. Please choose a different time or station.']); exit;
    }

    $totalCost = $duration * $station['rate_per_hour'];

    $ins = $db->prepare("
        INSERT INTO bookings (user_id, station_id, booking_date, start_time, duration_hrs, end_time, total_cost, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')
    ");
    $ins->execute([$user['user_id'], $stationId, $date, $startTime, $duration, $endTime, $totalCost]);

    // Mark station occupied if booking starts now (today, within current hour)
    if ($date === date('Y-m-d') && $startTime <= date('H:i:s') && $endTime >= date('H:i:s')) {
        $db->prepare("UPDATE stations SET status = 'occupied' WHERE station_id = ?")
           ->execute([$stationId]);
    }

    // Fix 6 & 8: recalculate hours from DB (excludes cancelled)
    recalcUserHours($user['user_id']);

    echo json_encode([
        'success'    => true,
        'total_cost' => number_format($totalCost, 2),
        'message'    => "Booking confirmed! RM " . number_format($totalCost, 2),
    ]);
    exit;
}

// ── CANCEL BOOKING ────────────────────────────────────────────
if ($action === 'cancel') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if (!$bookingId) { echo json_encode(['error' => 'Invalid booking.']); exit; }

    $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['error' => 'Booking not found.']); exit;
    }
    if ((int)$booking['user_id'] !== (int)$user['user_id'] && !$user['is_admin']) {
        echo json_encode(['error' => 'Unauthorised.']); exit;
    }
    if (in_array($booking['status'], ['completed','cancelled'])) {
        echo json_encode(['error' => 'This booking cannot be cancelled.']); exit;
    }

    $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?")
       ->execute([$bookingId]);

    // Free station if it was marked occupied for this booking
    if ($booking['booking_date'] === date('Y-m-d')) {
        $db->prepare("UPDATE stations SET status = 'available' WHERE station_id = ?")
           ->execute([$booking['station_id']]);
    }

    // Fix 6 & 8: recalculate hours, excluding this now-cancelled booking
    recalcUserHours((int)$booking['user_id']);

    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully.']);
    exit;
}

// ── EDIT BOOKING ──────────────────────────────────────────────
if ($action === 'edit') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $date      = $_POST['date']       ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $duration  = (float)($_POST['duration'] ?? 0);

    if (!$bookingId || !$date || !$startTime || !$duration) {
        echo json_encode(['error' => 'All fields are required.']); exit;
    }
    if ($date < date('Y-m-d')) {
        echo json_encode(['error' => 'Cannot reschedule to a past date.']); exit;
    }
    if ($duration <= 0 || $duration > 12) {
        echo json_encode(['error' => 'Duration must be between 1 and 12 hours.']); exit;
    }

    // Fetch original booking
    $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['error' => 'Booking not found.']); exit;
    }
    if ((int)$booking['user_id'] !== (int)$user['user_id']) {
        echo json_encode(['error' => 'Unauthorised.']); exit;
    }
    if (!in_array($booking['status'], ['scheduled'])) {
        echo json_encode(['error' => 'Only scheduled bookings can be edited.']); exit;
    }

    // Compute new end time
    $startDT = new DateTime($date . ' ' . $startTime);
    $endDT   = clone $startDT;
    $endDT->modify("+{$duration} hours");
    $endTime = $endDT->format('H:i:s');

    // Conflict check — exclude THIS booking from the check
    $stmt = $db->prepare("
        SELECT booking_id FROM bookings
        WHERE station_id  = ?
          AND booking_date = ?
          AND booking_id  != ?
          AND status NOT IN ('cancelled','completed')
          AND start_time   < ?
          AND end_time     > ?
    ");
    $stmt->execute([$booking['station_id'], $date, $bookingId, $endTime, $startTime]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Time slot conflict! Another booking overlaps this window. Please choose a different time.']); exit;
    }

    // Fetch station rate for cost recalculation
    $stmt = $db->prepare("SELECT rate_per_hour FROM stations WHERE station_id = ?");
    $stmt->execute([$booking['station_id']]);
    $station   = $stmt->fetch();
    $totalCost = $duration * $station['rate_per_hour'];

    // Update booking
    $db->prepare("
        UPDATE bookings
        SET booking_date = ?, start_time = ?, end_time = ?, duration_hrs = ?, total_cost = ?
        WHERE booking_id = ?
    ")->execute([$date, $startTime, $endTime, $duration, $totalCost, $bookingId]);

    // Recalculate user hours
    recalcUserHours((int)$user['user_id']);

    echo json_encode([
        'success'    => true,
        'total_cost' => number_format($totalCost, 2),
        'message'    => "Booking updated! New cost: RM " . number_format($totalCost, 2),
    ]);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);