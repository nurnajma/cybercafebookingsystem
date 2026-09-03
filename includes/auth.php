<?php
require_once __DIR__ . '/db.php';

function startSess(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Strict']);
        session_start();
    }
}

function csrfToken(): string {
    startSess();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }
}

function currentUser(): ?array {
    startSess();
    if (empty($_SESSION['uid'])) return null;
    $s = getDB()->prepare("SELECT * FROM users WHERE user_id = ?");
    $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}

// ── USER ONLY: blocks admins, redirects guests ────────────────
function requireUser(): array {
    $u = currentUser();
    if (!$u) { header('Location: /try_harder_v3/index.php'); exit; }
    if ($u['is_admin']) { header('Location: /try_harder_v3/admin/dashboard.php'); exit; }
    autoResetStaleStations();
    return $u;
}

// ── ADMIN ONLY: blocks regular users, redirects guests ────────
function requireAdmin(): array {
    $u = currentUser();
    if (!$u) { header('Location: /try_harder_v3/index.php'); exit; }
    if (!$u['is_admin']) { header('Location: /try_harder_v3/dashboard.php'); exit; }
    autoResetStaleStations();
    return $u;
}

function autoResetStaleStations(): void {
    $db = getDB();
    $db->exec("UPDATE bookings SET status='completed'
               WHERE status IN ('scheduled','active')
                 AND (booking_date < CURDATE()
                      OR (booking_date = CURDATE() AND end_time < CURTIME()))");
    $db->exec("UPDATE stations s SET s.status='available'
               WHERE s.status='occupied'
                 AND NOT EXISTS (
                     SELECT 1 FROM bookings b
                     WHERE b.station_id=s.station_id AND b.booking_date=CURDATE()
                       AND b.status IN ('scheduled','active')
                       AND b.start_time<=CURTIME() AND b.end_time>=CURTIME())");
}

function recalcUserHours(int $userId): void {
    $db   = getDB();
    $stmt = $db->prepare("SELECT COALESCE(SUM(duration_hrs),0) FROM bookings WHERE user_id=? AND status NOT IN ('cancelled')");
    $stmt->execute([$userId]);
    $hours = (float)$stmt->fetchColumn();
    $db->prepare("UPDATE users SET total_hours=?, membership=? WHERE user_id=?")->execute([$hours, calcMembership($hours), $userId]);
}

function calcMembership(float $h): string {
    if ($h>=500) return 'Platinum';
    if ($h>=200) return 'Gold';
    if ($h>=50)  return 'Silver';
    return 'Bronze';
}

function membershipColor(string $m): string {
    return match($m) {
        'Platinum' => 'text-purple-400 bg-purple-500/20 border-purple-500/30',
        'Gold'     => 'text-amber-400 bg-amber-500/20 border-amber-500/30',
        'Silver'   => 'text-gray-300 bg-gray-500/20 border-gray-500/30',
        default    => 'text-orange-400 bg-orange-500/20 border-orange-500/30',
    };
}

function checkLoginRateLimit(): bool {
    startSess();
    $now = time();
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], fn($t) => ($now-$t)<600);
    return count($_SESSION['login_attempts']) < 5;
}
function recordFailedLogin(): void { startSess(); $_SESSION['login_attempts'][] = time(); }
function clearLoginAttempts(): void { startSess(); unset($_SESSION['login_attempts']); }