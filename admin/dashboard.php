<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$adminUser    = requireAdmin();
$adminActivePage = 'dashboard';
$db           = getDB();

$totalStations  = $db->query("SELECT COUNT(*) FROM stations")->fetchColumn();
$occupied       = $db->query("SELECT COUNT(*) FROM stations WHERE status='occupied'")->fetchColumn();
$maintenance    = $db->query("SELECT COUNT(*) FROM stations WHERE status='maintenance'")->fetchColumn();
$available      = $db->query("SELECT COUNT(*) FROM stations WHERE status='available'")->fetchColumn();
$totalUsers     = $db->query("SELECT COUNT(*) FROM users WHERE is_admin=0")->fetchColumn();
$totalBookings  = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$revenue        = $db->query("SELECT COALESCE(SUM(total_cost),0) FROM bookings WHERE status != 'cancelled'")->fetchColumn();
$todayBookings  = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_date=CURDATE() AND status NOT IN ('cancelled')")->fetchColumn();
$occupancyRate  = $totalStations > 0 ? round(($occupied/$totalStations)*100) : 0;

$recentBookings = $db->query("
    SELECT b.*, u.username, s.station_code, s.zone
    FROM bookings b JOIN users u ON u.user_id=b.user_id JOIN stations s ON s.station_id=b.station_id
    ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();

$activeSessions = $db->query("
    SELECT b.*, u.username, s.station_code
    FROM bookings b JOIN users u ON u.user_id=b.user_id JOIN stations s ON s.station_id=b.station_id
    WHERE b.booking_date=CURDATE() AND b.status IN ('scheduled','active')
    ORDER BY b.start_time ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Admin Dashboard — TryHarder PC Hub</title>
<link rel="stylesheet" href="admin.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>

<div class="admin-main">
    <div class="admin-topbar">
        <span class="topbar-title">OPERATIONS OVERVIEW</span>
        <span class="topbar-badge">⬤ LIVE SYSTEM</span>
    </div>
    <div class="admin-body">

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card red">
                <div class="stat-label">Occupancy Rate</div>
                <div class="stat-value"><?= $occupancyRate ?>%</div>
                <div class="stat-sub"><?= $occupied ?>/<?= $totalStations ?> stations occupied</div>
            </div>
            <div class="stat-card cyan">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value" style="font-size:1.5rem">RM <?= number_format($revenue,2) ?></div>
                <div class="stat-sub">All confirmed bookings</div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Registered Players</div>
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-sub">Active member accounts</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-label">Today's Bookings</div>
                <div class="stat-value"><?= $todayBookings ?></div>
                <div class="stat-sub"><?= $totalBookings ?> total all time</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

            <!-- ACTIVE SESSIONS -->
            <div class="section-card">
                <div class="section-card-head">
                    <div class="section-card-title"><i class="fa-solid fa-circle-dot" style="color:var(--green)"></i>LIVE SESSIONS TODAY</div>
                    <span class="badge badge-green"><?= count($activeSessions) ?> active</span>
                </div>
                <div class="section-card-body" style="padding:0">
                    <?php if (!$activeSessions): ?>
                    <p style="padding:1.5rem;color:var(--text3);font-size:.85rem;text-align:center">No active sessions right now.</p>
                    <?php else: foreach ($activeSessions as $s): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.5rem;border-bottom:1px solid var(--border)">
                        <div style="display:flex;align-items:center;gap:.75rem">
                            <span style="width:8px;height:8px;border-radius:50%;background:var(--green);flex-shrink:0;animation:pulse 2s infinite"></span>
                            <div>
                                <div style="font-family:var(--font-head);font-size:.75rem;color:var(--text)"><?= htmlspecialchars($s['station_code']) ?></div>
                                <div style="font-size:.75rem;color:var(--text3)"><?= htmlspecialchars($s['username']) ?> · <?= substr($s['start_time'],0,5) ?>–<?= substr($s['end_time'],0,5) ?></div>
                            </div>
                        </div>
                        <div style="display:flex;gap:.4rem">
                            <button class="btn-warn-sm" onclick="warnStation('<?= $s['station_code'] ?>')">Warn</button>
                            <button class="btn-danger-sm" onclick="killStation('<?= $s['station_code'] ?>')">Kill</button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- STATION STATUS OVERVIEW -->
            <div class="section-card">
                <div class="section-card-head">
                    <div class="section-card-title"><i class="fa-solid fa-circle-nodes"></i>STATION OVERVIEW</div>
                    <a href="stations.php" style="font-size:.75rem;color:var(--red)">Manage →</a>
                </div>
                <div class="section-card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;text-align:center">
                    <div style="background:rgba(34,197,94,.07);border:1px solid rgba(34,197,94,.2);border-radius:6px;padding:1rem">
                        <div style="font-family:var(--font-head);font-size:2rem;color:var(--green)"><?= $available ?></div>
                        <div style="font-size:.72rem;color:var(--text3);letter-spacing:1px;text-transform:uppercase;margin-top:.3rem">Available</div>
                    </div>
                    <div style="background:rgba(230,57,70,.07);border:1px solid rgba(230,57,70,.2);border-radius:6px;padding:1rem">
                        <div style="font-family:var(--font-head);font-size:2rem;color:var(--red)"><?= $occupied ?></div>
                        <div style="font-size:.72rem;color:var(--text3);letter-spacing:1px;text-transform:uppercase;margin-top:.3rem">Occupied</div>
                    </div>
                    <div style="background:rgba(234,179,8,.07);border:1px solid rgba(234,179,8,.2);border-radius:6px;padding:1rem">
                        <div style="font-family:var(--font-head);font-size:2rem;color:var(--yellow)"><?= $maintenance ?></div>
                        <div style="font-size:.72rem;color:var(--text3);letter-spacing:1px;text-transform:uppercase;margin-top:.3rem">Maintenance</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT BOOKINGS -->
        <div class="section-card">
            <div class="section-card-head">
                <div class="section-card-title"><i class="fa-solid fa-table-list"></i>RECENT BOOKINGS</div>
                <a href="bookings.php" style="font-size:.75rem;color:var(--red)">View all →</a>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Player</th><th>Station</th><th>Zone</th><th>Date</th><th>Block</th><th>Cost</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentBookings as $b):
                        $bc = match($b['status']) { 'scheduled'=>'badge-cyan','active'=>'badge-green','completed'=>'badge-gray','cancelled'=>'badge-red', default=>'badge-gray' };
                    ?>
                    <tr>
                        <td><span style="font-family:var(--font-mono);color:var(--text3);font-size:.75rem">#<?= $b['booking_id'] ?></span></td>
                        <td><strong style="color:var(--text)"><?= htmlspecialchars($b['username']) ?></strong></td>
                        <td><span style="font-family:var(--font-head);font-size:.75rem;color:var(--red)"><?= htmlspecialchars($b['station_code']) ?></span></td>
                        <td><span style="font-size:.78rem"><?= $b['zone'] ?></span></td>
                        <td><?= $b['booking_date'] ?></td>
                        <td style="font-family:var(--font-mono);font-size:.78rem"><?= substr($b['start_time'],0,5) ?>–<?= substr($b['end_time'],0,5) ?></td>
                        <td><strong style="color:var(--text)">RM <?= number_format($b['total_cost'],2) ?></strong></td>
                        <td><span class="badge <?= $bc ?>"><?= $b['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="admin-footer">
        <span>&copy; 2026 TryHarder PC Hub — Admin Core</span>
    </div>
</div>

<div id="toast-container"></div>
<div class="modal-overlay" id="modal-action">
    <div class="modal">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-title" id="modal-title">ACTION</div>
        <p id="modal-msg" style="color:var(--text2);font-size:.9rem;line-height:1.6"></p>
        <div style="margin-top:1.25rem;display:flex;gap:.75rem">
            <button class="btn btn-red" id="modal-confirm-btn">CONFIRM</button>
            <button class="btn btn-ghost" onclick="closeModal()">CANCEL</button>
        </div>
    </div>
</div>

<script>
function showToast(msg, type='info') {
    let c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3100);
}
function closeModal() { document.getElementById('modal-action').classList.remove('open'); }

function warnStation(code) {
    document.getElementById('modal-title').textContent = '⚠ SEND WARNING';
    document.getElementById('modal-msg').textContent   = `Send a warning alert to station ${code}? The active user will be notified.`;
    document.getElementById('modal-confirm-btn').onclick = async () => {
        closeModal();
        const fd = new FormData(); fd.append('action','warn_station'); fd.append('station_code',code);
        const data = await fetch('../api_admin.php',{method:'POST',body:fd}).then(r=>r.json());
        showToast(data.message || data.error, data.success ? 'warn' : 'error');
    };
    document.getElementById('modal-action').classList.add('open');
}

function killStation(code) {
    document.getElementById('modal-title').textContent = '⚡ FORCE DISCONNECT';
    document.getElementById('modal-msg').textContent   = `Force disconnect station ${code}? This will immediately cancel the active booking and free the station.`;
    document.getElementById('modal-confirm-btn').onclick = async () => {
        closeModal();
        const fd = new FormData(); fd.append('action','kill_station'); fd.append('station_code',code);
        const data = await fetch('../api_admin.php',{method:'POST',body:fd}).then(r=>r.json());
        showToast(data.message || data.error, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1200);
    };
    document.getElementById('modal-action').classList.add('open');
}
</script>
<style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
</body>
</html>