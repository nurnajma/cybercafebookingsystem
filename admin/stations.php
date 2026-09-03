<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$adminUser       = requireAdmin();
$adminActivePage = 'stations';
$db              = getDB();
$stations        = $db->query("SELECT * FROM stations ORDER BY zone DESC, station_code ASC")->fetchAll();

// Pre-load bookings per station for the modal (today + upcoming)
$stationBookings = [];
$rows = $db->query("
    SELECT b.*, u.username
    FROM bookings b
    JOIN users u ON u.user_id = b.user_id
    WHERE b.status NOT IN ('cancelled')
    ORDER BY b.booking_date DESC, b.start_time DESC
    LIMIT 200
")->fetchAll();
foreach ($rows as $r) {
    $stationBookings[$r['station_id']][] = $r;
}

$av = count(array_filter($stations, fn($s)=>$s['status']==='available'));
$oc = count(array_filter($stations, fn($s)=>$s['status']==='occupied'));
$mn = count(array_filter($stations, fn($s)=>$s['status']==='maintenance'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Stations — TryHarder Admin</title>
<link rel="stylesheet" href="admin.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
.action-btn {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .3rem .65rem; border-radius: 4px;
    font-size: .72rem; font-weight: 700; letter-spacing: .5px;
    cursor: pointer; transition: all .2s; white-space: nowrap;
    font-family: var(--font-body); border: 1px solid;
}
.btn-view   { background:rgba(56,189,248,.08); border-color:rgba(56,189,248,.25); color:#38bdf8; }
.btn-view:hover   { background:rgba(56,189,248,.2); }
.btn-reset  { background:rgba(34,197,94,.08);  border-color:rgba(34,197,94,.25);  color:#22c55e; }
.btn-reset:hover  { background:rgba(34,197,94,.2); }
.btn-edit   { background:rgba(234,179,8,.08);  border-color:rgba(234,179,8,.25);  color:#eab308; }
.btn-edit:hover   { background:rgba(234,179,8,.2); }
.btn-warn   { background:rgba(234,179,8,.08);  border-color:rgba(234,179,8,.25);  color:#eab308; }
.btn-warn:hover   { background: #eab308; color:#000; }
.btn-kill   { background:rgba(230,57,70,.08);  border-color:rgba(230,57,70,.25);  color:var(--red); }
.btn-kill:hover   { background: var(--red); color:#fff; }
.actions-cell { display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; }

/* Modal overrides */
.modal { max-width: 560px; }
.modal-lg { max-width: 700px; }
.modal-section { margin-bottom: 1.2rem; }
.modal-section-title {
    font-family: var(--font-head); font-size: .7rem; letter-spacing: 1.5px;
    color: var(--text3); text-transform: uppercase; margin-bottom: .6rem;
    padding-bottom: .4rem; border-bottom: 1px solid var(--border);
}
.info-row { display:flex; justify-content:space-between; align-items:center; padding: .5rem 0; border-bottom: 1px solid var(--border); }
.info-row:last-child { border-bottom: none; }
.info-label { font-size: .8rem; color: var(--text3); }
.info-value { font-size: .85rem; font-weight: 600; color: var(--text); }
.form-input-modal {
    width: 100%; background: var(--bg2); border: 1px solid var(--border2);
    border-radius: 5px; padding: .55rem .9rem; color: var(--text);
    font-size: .88rem; outline: none; font-family: var(--font-body);
    transition: border-color .2s;
}
.form-input-modal:focus { border-color: var(--red); }
.mini-booking-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .5rem .75rem; background: var(--bg2);
    border: 1px solid var(--border); border-radius: 5px; margin-bottom: .4rem;
    font-size: .8rem;
}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="admin-main">
    <div class="admin-topbar">
        <span class="topbar-title">STATION MANAGEMENT</span>
        <span class="topbar-badge">CONTROL MATRIX</span>
    </div>
    <div class="admin-body">
        <div class="page-header">
            <div class="page-title">STATION <span>CONTROL</span></div>
            <div class="page-sub">Update status, view bookings, reset stations, and edit specs in real time.</div>
        </div>

        <!-- QUICK STATS -->
        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
            <div class="stat-card green"><div class="stat-label">Available</div><div class="stat-value"><?= $av ?></div></div>
            <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value"><?= $oc ?></div></div>
            <div class="stat-card yellow"><div class="stat-label">Maintenance</div><div class="stat-value"><?= $mn ?></div></div>
        </div>

        <?php foreach (['VIP','Standard'] as $zone):
            $zoneStations = array_filter($stations, fn($s) => $s['zone'] === $zone);
            $zoneColor  = $zone === 'VIP' ? 'var(--yellow)' : 'var(--cyan)';
            $zoneIcon   = $zone === 'VIP' ? 'crown' : 'desktop';
            $zoneBadge  = $zone === 'VIP' ? 'badge-yellow' : 'badge-cyan';
            $zoneRate   = $zone === 'VIP' ? 'RM 6.00 / hr' : 'RM 5.00 / hr';
        ?>
        <div class="section-card">
            <div class="section-card-head">
                <div class="section-card-title">
                    <i class="fa-solid fa-<?= $zoneIcon ?>" style="color:<?= $zoneColor ?>"></i>
                    <?= strtoupper($zone) ?> ZONE
                </div>
                <span class="badge <?= $zoneBadge ?>"><?= $zoneRate ?></span>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Station</th>
                            <th>Specs</th>
                            <th>Rate</th>
                            <th>Status</th>
                            <th>Update Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($zoneStations as $s):
                        $bc = match($s['status']) {
                            'available'   => 'badge-green',
                            'occupied'    => 'badge-red',
                            'maintenance' => 'badge-yellow',
                            default       => 'badge-gray'
                        };
                        $bookingCount = count($stationBookings[$s['station_id']] ?? []);
                        $stationJson  = htmlspecialchars(json_encode([
                            'id'           => $s['station_id'],
                            'code'         => $s['station_code'],
                            'zone'         => $s['zone'],
                            'specs'        => $s['specs'],
                            'rate'         => $s['rate_per_hour'],
                            'status'       => $s['status'],
                            'bookingCount' => $bookingCount,
                        ]), ENT_QUOTES);

                        // Encode bookings for this station
                        $booksJson = htmlspecialchars(json_encode(
                            array_map(fn($b) => [
                                'id'     => $b['booking_id'],
                                'user'   => $b['username'],
                                'date'   => $b['booking_date'],
                                'start'  => substr($b['start_time'],0,5),
                                'end'    => substr($b['end_time'],0,5),
                                'dur'    => $b['duration_hrs'],
                                'cost'   => number_format($b['total_cost'],2),
                                'status' => $b['status'],
                            ], $stationBookings[$s['station_id']] ?? [])
                        ), ENT_QUOTES);
                    ?>
                    <tr>
                        <td>
                            <strong style="font-family:var(--font-head);font-size:.8rem;color:<?= $zoneColor ?>">
                                <?= htmlspecialchars($s['station_code']) ?>
                            </strong>
                        </td>
                        <td style="font-size:.8rem;color:var(--text2)"><?= htmlspecialchars($s['specs']) ?></td>
                        <td style="font-family:var(--font-mono);font-size:.8rem">RM <?= number_format($s['rate_per_hour'],2) ?></td>
                        <td><span class="badge <?= $bc ?>"><?= $s['status'] ?></span></td>
                        <td>
                            <select class="form-select" onchange="updateStation(<?= $s['station_id'] ?>,this.value)">
                                <option value="available"   <?= $s['status']==='available'   ?'selected':'' ?>>Available</option>
                                <option value="occupied"    <?= $s['status']==='occupied'    ?'selected':'' ?>>Occupied</option>
                                <option value="maintenance" <?= $s['status']==='maintenance' ?'selected':'' ?>>Maintenance</option>
                            </select>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <!-- View Bookings -->
                                <button class="action-btn btn-view"
                                    onclick="openBookingsModal('<?= $s['station_code'] ?>',<?= $booksJson ?>)">
                                    <i class="fa-solid fa-calendar-days"></i> Bookings
                                    <?php if ($bookingCount > 0): ?>
                                    <span style="background:rgba(56,189,248,.25);border-radius:3px;padding:0 .3rem;font-size:.65rem"><?= $bookingCount ?></span>
                                    <?php endif; ?>
                                </button>
                                <!-- Reset to Available -->
                                <?php if ($s['status'] !== 'available'): ?>
                                <button class="action-btn btn-reset"
                                    onclick="resetStation(<?= $s['station_id'] ?>,'<?= $s['station_code'] ?>')">
                                    <i class="fa-solid fa-rotate-left"></i> Reset
                                </button>
                                <?php endif; ?>
                                <!-- Edit Specs -->
                                <button class="action-btn btn-edit"
                                    onclick="openEditModal(<?= $stationJson ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <!-- Warn / Kill (occupied only) -->
                                <?php if ($s['status']==='occupied'): ?>
                                <button class="action-btn btn-warn"
                                    onclick="warnStation('<?= $s['station_code'] ?>')">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Warn
                                </button>
                                <button class="action-btn btn-kill"
                                    onclick="killStation('<?= $s['station_code'] ?>')">
                                    <i class="fa-solid fa-power-off"></i> Kill
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="admin-footer"><span>&copy; 2026 TryHarder PC Hub — Admin Core</span></div>
</div>

<!-- ── MODAL: VIEW BOOKINGS ──────────────────────────────────── -->
<div class="modal-overlay" id="modal-bookings">
    <div class="modal modal-lg">
        <button class="modal-close" onclick="closeModal('modal-bookings')">✕</button>
        <div class="modal-title"><i class="fa-solid fa-calendar-days" style="margin-right:.5rem"></i><span id="modal-bookings-title">BOOKINGS</span></div>
        <div id="modal-bookings-body" style="max-height:420px;overflow-y:auto"></div>
    </div>
</div>

<!-- ── MODAL: EDIT SPECS ─────────────────────────────────────── -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
        <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="margin-right:.5rem"></i>EDIT STATION</div>
        <input type="hidden" id="edit-station-id"/>
        <div class="modal-section">
            <div class="modal-section-title">Station Info</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem">
                <div>
                    <div style="font-size:.72rem;color:var(--text3);letter-spacing:1px;margin-bottom:.3rem;text-transform:uppercase">Station Code</div>
                    <div id="edit-station-code" style="font-family:var(--font-head);font-size:.9rem;color:var(--red)"></div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text3);letter-spacing:1px;margin-bottom:.3rem;text-transform:uppercase">Zone</div>
                    <div id="edit-station-zone" style="font-size:.9rem;color:var(--text)"></div>
                </div>
            </div>
        </div>
        <div class="modal-section">
            <div class="modal-section-title">Edit Specs & Rate</div>
            <div style="display:flex;flex-direction:column;gap:.75rem">
                <div>
                    <label class="form-label">Hardware Specs</label>
                    <input type="text" id="edit-specs" class="form-input-modal" placeholder="e.g. RTX 4090 • 32GB DDR5 • 360Hz Display"/>
                </div>
                <div>
                    <label class="form-label">Rate Per Hour (RM)</label>
                    <input type="number" id="edit-rate" class="form-input-modal" step="0.50" min="1" placeholder="e.g. 6.00"/>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1rem">
            <button onclick="saveEdit()" class="btn btn-red" style="flex:1;justify-content:center">
                <i class="fa-solid fa-floppy-disk"></i> SAVE CHANGES
            </button>
            <button onclick="closeModal('modal-edit')" class="btn btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<!-- ── MODAL: CONFIRM RESET ──────────────────────────────────── -->
<div class="modal-overlay" id="modal-reset">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-reset')">✕</button>
        <div class="modal-title"><i class="fa-solid fa-rotate-left" style="margin-right:.5rem;color:var(--green)"></i>RESET STATION</div>
        <p id="modal-reset-msg" style="color:var(--text2);font-size:.9rem;line-height:1.6;margin-bottom:1.25rem"></p>
        <div style="display:flex;gap:.75rem">
            <button id="modal-reset-confirm" class="btn btn-red" style="flex:1;justify-content:center">
                <i class="fa-solid fa-rotate-left"></i> CONFIRM RESET
            </button>
            <button onclick="closeModal('modal-reset')" class="btn btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script>
// ── UTILS ─────────────────────────────────────────────────────
function showToast(msg, type='info') {
    let c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3100);
}
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});

// ── UPDATE STATUS DROPDOWN ────────────────────────────────────
async function updateStation(id, status) {
    const fd = new FormData();
    fd.append('action','update_station'); fd.append('station_id',id); fd.append('status',status);
    const data = await fetch('../api_admin.php',{method:'POST',body:fd}).then(r=>r.json());
    showToast(data.message || data.error, data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => location.reload(), 800);
}

// ── VIEW BOOKINGS MODAL ───────────────────────────────────────
function openBookingsModal(code, bookings) {
    document.getElementById('modal-bookings-title').textContent = code + ' — BOOKING HISTORY';
    const body = document.getElementById('modal-bookings-body');

    if (!bookings || bookings.length === 0) {
        body.innerHTML = '<p style="color:var(--text3);font-size:.85rem;text-align:center;padding:2rem">No bookings found for this station.</p>';
    } else {
        const statusColors = {
            scheduled: '#38bdf8', active: '#22c55e',
            completed: '#6b7280', cancelled: '#e63946'
        };
        body.innerHTML = bookings.map(b => `
            <div class="mini-booking-row">
                <div style="display:flex;flex-direction:column;gap:.15rem">
                    <span style="font-weight:700;color:var(--text)">${b.user}</span>
                    <span style="color:var(--text3);font-size:.75rem">${b.date} · ${b.start}–${b.end} · ${b.dur}h</span>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem">
                    <span style="font-weight:700;color:var(--text)">RM ${b.cost}</span>
                    <span style="font-size:.68rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;
                        padding:.15rem .5rem;border-radius:3px;
                        color:${statusColors[b.status]||'#6b7280'};
                        background:${statusColors[b.status]||'#6b7280'}22;
                        border:1px solid ${statusColors[b.status]||'#6b7280'}44">
                        ${b.status}
                    </span>
                </div>
            </div>
        `).join('');
    }
    openModal('modal-bookings');
}

// ── RESET STATION MODAL ───────────────────────────────────────
function resetStation(id, code) {
    document.getElementById('modal-reset-msg').textContent =
        `Reset station ${code} to Available? Any active session will be cleared and the station will be freed immediately.`;
    document.getElementById('modal-reset-confirm').onclick = async () => {
        closeModal('modal-reset');
        const fd = new FormData();
        fd.append('action','update_station'); fd.append('station_id',id); fd.append('status','available');
        const data = await fetch('../api_admin.php',{method:'POST',body:fd}).then(r=>r.json());
        showToast(data.message || data.error, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 800);
    };
    openModal('modal-reset');
}

// ── EDIT SPECS MODAL ──────────────────────────────────────────
function openEditModal(station) {
    document.getElementById('edit-station-id').value   = station.id;
    document.getElementById('edit-station-code').textContent = station.code;
    document.getElementById('edit-station-zone').textContent = station.zone + ' Zone';
    document.getElementById('edit-specs').value        = station.specs;
    document.getElementById('edit-rate').value         = station.rate;
    openModal('modal-edit');
}

async function saveEdit() {
    const id    = document.getElementById('edit-station-id').value;
    const specs = document.getElementById('edit-specs').value.trim();
    const rate  = document.getElementById('edit-rate').value;
    if (!specs || !rate) { showToast('Please fill in all fields.', 'error'); return; }

    const fd = new FormData();
    fd.append('action','edit_station');
    fd.append('station_id', id);
    fd.append('specs', specs);
    fd.append('rate',  rate);
    const data = await fetch('../api_admin.php',{method:'POST',body:fd}).then(r=>r.json());
    showToast(data.message || data.error, data.success ? 'success' : 'error');
    if (data.success) { closeModal('modal-edit'); setTimeout(() => location.reload(), 800); }
}

// ── WARN / KILL ───────────────────────────────────────────────
async function warnStation(code) {
    const fd = new FormData();
    fd.append('action','warn_station'); fd.append('station_code',code);
    const data = await fetch('../api_admin.php',{method:'POST',body:fd}).then(r=>r.json());
    showToast(data.message || data.error, 'warn');
}
async function killStation(code) {
    if (!confirm(`Force disconnect ${code}? This cancels the active booking.`)) return;
    const fd = new FormData();
    fd.append('action','kill_station'); fd.append('station_code',code);
    const data = await fetch('../api_admin.php',{method:'POST',body:fd}).then(r=>r.json());
    showToast(data.message || data.error, data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => location.reload(), 1200);
}
</script>
</body>
</html>