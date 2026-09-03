<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$adminUser       = requireAdmin();
$adminActivePage = 'bookings';
$db              = getDB();

// Filter support
$filterStatus = $_GET['status'] ?? 'all';
$filterZone   = $_GET['zone']   ?? 'all';

$where = "WHERE 1=1";
$params = [];
if ($filterStatus !== 'all') { $where .= " AND b.status=?"; $params[] = $filterStatus; }
if ($filterZone   !== 'all') { $where .= " AND s.zone=?";   $params[] = $filterZone; }

$stmt = $db->prepare("
    SELECT b.*, u.username, s.station_code, s.zone
    FROM bookings b JOIN users u ON u.user_id=b.user_id JOIN stations s ON s.station_id=b.station_id
    $where ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$stats = [
    'total'     => $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'scheduled' => $db->query("SELECT COUNT(*) FROM bookings WHERE status='scheduled'")->fetchColumn(),
    'completed' => $db->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn(),
    'cancelled' => $db->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn(),
    'revenue'   => $db->query("SELECT COALESCE(SUM(total_cost),0) FROM bookings WHERE status!='cancelled'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Bookings — TryHarder Admin</title>
<link rel="stylesheet" href="admin.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="admin-main">
    <div class="admin-topbar">
        <span class="topbar-title">BOOKING MANAGEMENT</span>
        <span class="topbar-badge"><?= count($bookings) ?> RECORDS</span>
    </div>
    <div class="admin-body">
        <div class="page-header">
            <div class="page-title">ALL <span>BOOKINGS</span></div>
            <div class="page-sub">View, filter, and manage all player bookings across the facility.</div>
        </div>

        <div class="stat-grid" style="margin-bottom:1.5rem">
            <div class="stat-card cyan"><div class="stat-label">Total Revenue</div><div class="stat-value" style="font-size:1.4rem">RM <?= number_format($stats['revenue'],2) ?></div></div>
            <div class="stat-card yellow"><div class="stat-label">Scheduled</div><div class="stat-value"><?= $stats['scheduled'] ?></div></div>
            <div class="stat-card green"><div class="stat-label">Completed</div><div class="stat-value"><?= $stats['completed'] ?></div></div>
            <div class="stat-card red"><div class="stat-label">Cancelled</div><div class="stat-value"><?= $stats['cancelled'] ?></div></div>
        </div>

        <!-- FILTERS -->
        <div style="display:flex;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap;align-items:center">
            <form method="GET" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
                <div>
                    <label class="form-label" style="display:inline;margin-right:.4rem">Status:</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all"      <?= $filterStatus==='all'       ?'selected':'' ?>>All</option>
                        <option value="scheduled"<?= $filterStatus==='scheduled' ?'selected':'' ?>>Scheduled</option>
                        <option value="active"   <?= $filterStatus==='active'    ?'selected':'' ?>>Active</option>
                        <option value="completed"<?= $filterStatus==='completed' ?'selected':'' ?>>Completed</option>
                        <option value="cancelled"<?= $filterStatus==='cancelled' ?'selected':'' ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="display:inline;margin-right:.4rem">Zone:</label>
                    <select name="zone" class="form-select" onchange="this.form.submit()">
                        <option value="all"     <?= $filterZone==='all'      ?'selected':'' ?>>All Zones</option>
                        <option value="VIP"     <?= $filterZone==='VIP'      ?'selected':'' ?>>VIP</option>
                        <option value="Standard"<?= $filterZone==='Standard' ?'selected':'' ?>>Standard</option>
                    </select>
                </div>
                <?php if ($filterStatus!=='all' || $filterZone!=='all'): ?>
                <a href="/try_harder_v3/admin/bookings.php" style="font-size:.8rem;color:var(--text3)">Clear filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="section-card">
            <div class="admin-table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Player</th><th>Station</th><th>Zone</th><th>Date</th><th>Time Block</th><th>Duration</th><th>Cost</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (!$bookings): ?>
                    <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text3)">No bookings found.</td></tr>
                    <?php else: foreach ($bookings as $b):
                        $bc = match($b['status']){'scheduled'=>'badge-cyan','active'=>'badge-green','completed'=>'badge-gray','cancelled'=>'badge-red',default=>'badge-gray'};
                    ?>
                    <tr>
                        <td><span style="font-family:var(--font-mono);color:var(--text3);font-size:.75rem">#<?= $b['booking_id'] ?></span></td>
                        <td><strong style="color:var(--text)"><?= htmlspecialchars($b['username']) ?></strong></td>
                        <td><span style="font-family:var(--font-head);font-size:.78rem;color:var(--red)"><?= htmlspecialchars($b['station_code']) ?></span></td>
                        <td><span style="font-size:.78rem"><?= $b['zone'] ?></span></td>
                        <td style="font-size:.85rem"><?= $b['booking_date'] ?></td>
                        <td style="font-family:var(--font-mono);font-size:.78rem"><?= substr($b['start_time'],0,5) ?>–<?= substr($b['end_time'],0,5) ?></td>
                        <td style="font-size:.85rem"><?= $b['duration_hrs'] ?>h</td>
                        <td><strong style="color:var(--text)">RM <?= number_format($b['total_cost'],2) ?></strong></td>
                        <td><span class="badge <?= $bc ?>"><?= $b['status'] ?></span></td>
                        <td>
                            <?php if (!in_array($b['status'],['cancelled','completed'])): ?>
                            <button class="btn-danger-sm" onclick="cancelBooking(<?= $b['booking_id'] ?>)">Cancel</button>
                            <?php else: echo '<span style="color:var(--text3);font-size:.75rem">—</span>'; endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="admin-footer"><span>&copy; 2026 TryHarder PC Hub — Admin Core</span></div>
</div>
<div id="toast-container"></div>
<script>
function showToast(msg,type='info'){let c=document.getElementById('toast-container');const t=document.createElement('div');t.className=`toast ${type}`;t.textContent=msg;c.appendChild(t);setTimeout(()=>t.remove(),3100);}
async function cancelBooking(id){
    if(!confirm(`Cancel booking #${id}? This cannot be undone.`))return;
    const fd=new FormData();fd.append('action','cancel');fd.append('booking_id',id);
    const data=await fetch('/try_harder_v3/api_booking.php',{method:'POST',body:fd}).then(r=>r.json());
    showToast(data.message||data.error,data.success?'success':'error');
    if(data.success)setTimeout(()=>location.reload(),900);
}
</script>
</body>
</html>