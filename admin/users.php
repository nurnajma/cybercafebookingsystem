<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/avatars.php';
$adminUser       = requireAdmin();
$adminActivePage = 'users';
$db              = getDB();

$users = $db->query("
    SELECT u.*, COUNT(b.booking_id) as booking_count,
           COALESCE(SUM(CASE WHEN b.status!='cancelled' THEN b.total_cost ELSE 0 END),0) as total_spent
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.user_id
    WHERE u.is_admin = 0
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
")->fetchAll();

$totalUsers   = count($users);
$goldPlus     = count(array_filter($users, fn($u) => in_array($u['membership'],['Gold','Platinum'])));
$totalRevenue = array_sum(array_column($users,'total_spent'));

// Pre-load bookings per user for the modal
$allBookings = $db->query("
    SELECT b.*, s.station_code, s.zone
    FROM bookings b
    JOIN stations s ON s.station_id = b.station_id
    ORDER BY b.created_at DESC
")->fetchAll();
$bookingsByUser = [];
foreach ($allBookings as $b) $bookingsByUser[$b['user_id']][] = $b;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Users — TryHarder Admin</title>
<link rel="stylesheet" href="admin.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
.action-btn { display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:4px;font-size:.72rem;font-weight:700;letter-spacing:.5px;cursor:pointer;transition:all .2s;white-space:nowrap;font-family:var(--font-body);border:1px solid; }
.btn-view   { background:rgba(56,189,248,.08); border-color:rgba(56,189,248,.25); color:#38bdf8; }
.btn-view:hover   { background:rgba(56,189,248,.2); }
.btn-reset  { background:rgba(234,179,8,.08);  border-color:rgba(234,179,8,.25);  color:#eab308; }
.btn-reset:hover  { background:rgba(234,179,8,.2); }
.btn-delete { background:rgba(230,57,70,.08);  border-color:rgba(230,57,70,.25);  color:var(--red); }
.btn-delete:hover { background:rgba(230,57,70,.2); }
.actions-cell { display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; }
.modal-lg { max-width:680px; }
.mini-booking-row { display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:var(--bg2);border:1px solid var(--border);border-radius:5px;margin-bottom:.4rem;font-size:.8rem; }
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="admin-main">
    <div class="admin-topbar">
        <span class="topbar-title">USER MANAGEMENT</span>
        <span class="topbar-badge"><?= $totalUsers ?> REGISTERED</span>
    </div>
    <div class="admin-body">
        <div class="page-header">
            <div class="page-title">PLAYER <span>REGISTRY</span></div>
            <div class="page-sub">View booking history, reset membership tiers, and manage registered accounts.</div>
        </div>

        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
            <div class="stat-card red"><div class="stat-label">Total Members</div><div class="stat-value"><?= $totalUsers ?></div></div>
            <div class="stat-card yellow"><div class="stat-label">Gold / Platinum</div><div class="stat-value"><?= $goldPlus ?></div></div>
            <div class="stat-card cyan"><div class="stat-label">Total Member Revenue</div><div class="stat-value" style="font-size:1.4rem">RM <?= number_format($totalRevenue,2) ?></div></div>
        </div>

        <div class="section-card">
            <div class="section-card-head">
                <div class="section-card-title"><i class="fa-solid fa-users"></i>ALL PLAYERS</div>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Full Name</th>
                            <th>Membership</th>
                            <th>Hours</th>
                            <th>Sessions</th>
                            <th>Spent</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u):
                        $mc = match($u['membership']){
                            'Platinum' => 'badge-orange',
                            'Gold'     => 'badge-yellow',
                            'Silver'   => 'badge-cyan',
                            default    => 'badge-gray'
                        };
                        $userBookings = $bookingsByUser[$u['user_id']] ?? [];
                        $booksJson = htmlspecialchars(json_encode(
                            array_map(fn($b) => [
                                'id'     => $b['booking_id'],
                                'code'   => $b['station_code'],
                                'zone'   => $b['zone'],
                                'date'   => $b['booking_date'],
                                'start'  => substr($b['start_time'],0,5),
                                'end'    => substr($b['end_time'],0,5),
                                'dur'    => $b['duration_hrs'],
                                'cost'   => number_format($b['total_cost'],2),
                                'status' => $b['status'],
                            ], $userBookings)
                        ), ENT_QUOTES);
                        $userJson = htmlspecialchars(json_encode([
                            'id'         => $u['user_id'],
                            'username'   => $u['username'],
                            'membership' => $u['membership'],
                        ]), ENT_QUOTES);
                    ?>
                    <tr id="user-row-<?= $u['user_id'] ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:.7rem">
                                <?= renderAvatar($u['avatar_key'] ?? 'gamepad', 'xs') ?>
                                <strong style="color:var(--text)"><?= htmlspecialchars($u['username']) ?></strong>
                            </div>
                        </td>
                        <td style="font-size:.85rem"><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><span class="badge <?= $mc ?>" id="badge-<?= $u['user_id'] ?>"><?= $u['membership'] ?></span></td>
                        <td style="font-family:var(--font-head);font-size:.85rem;color:var(--text)"><?= number_format($u['total_hours'],1) ?>h</td>
                        <td style="font-size:.85rem"><?= $u['booking_count'] ?></td>
                        <td><strong style="color:var(--text)">RM <?= number_format($u['total_spent'],2) ?></strong></td>
                        <td style="font-size:.8rem;color:var(--text3)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="actions-cell">
                                <!-- View Bookings -->
                                <button class="action-btn btn-view"
                                    onclick="openBookingsModal('<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>',<?= $booksJson ?>)">
                                    <i class="fa-solid fa-clock-rotate-left"></i> History
                                    <?php if (count($userBookings) > 0): ?>
                                    <span style="background:rgba(56,189,248,.2);border-radius:3px;padding:0 .3rem;font-size:.65rem"><?= count($userBookings) ?></span>
                                    <?php endif; ?>
                                </button>
                                <!-- Reset Membership -->
                                <?php if ($u['membership'] !== 'Bronze'): ?>
                                <button class="action-btn btn-reset"
                                    onclick="openResetModal(<?= $u['user_id'] ?>,'<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>','<?= $u['membership'] ?>')">
                                    <i class="fa-solid fa-rotate-left"></i> Reset
                                </button>
                                <?php endif; ?>
                                <!-- Delete User -->
                                <button class="action-btn btn-delete"
                                    onclick="openDeleteModal(<?= $u['user_id'] ?>,'<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="admin-footer"><span>&copy; 2026 TryHarder PC Hub — Admin Core</span></div>
</div>

<!-- ── MODAL: BOOKING HISTORY ────────────────────────────────── -->
<div class="modal-overlay" id="modal-history">
    <div class="modal modal-lg">
        <button class="modal-close" onclick="closeModal('modal-history')">✕</button>
        <div class="modal-title"><i class="fa-solid fa-clock-rotate-left" style="margin-right:.5rem"></i><span id="history-title">BOOKING HISTORY</span></div>
        <div id="history-body" style="max-height:440px;overflow-y:auto"></div>
    </div>
</div>

<!-- ── MODAL: RESET MEMBERSHIP ───────────────────────────────── -->
<div class="modal-overlay" id="modal-reset">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-reset')">✕</button>
        <div class="modal-title"><i class="fa-solid fa-rotate-left" style="margin-right:.5rem;color:var(--yellow)"></i>RESET MEMBERSHIP</div>
        <p id="reset-msg" style="color:var(--text2);font-size:.9rem;line-height:1.7;margin-bottom:1.25rem"></p>
        <div style="background:rgba(234,179,8,.07);border:1px solid rgba(234,179,8,.2);border-radius:6px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:var(--yellow)">
            <i class="fa-solid fa-triangle-exclamation" style="margin-right:.4rem"></i>
            This resets their tier to Bronze. Their total gaming hours will also be reset to 0.
        </div>
        <div style="display:flex;gap:.75rem">
            <button id="reset-confirm-btn" class="btn btn-red" style="flex:1;justify-content:center">
                <i class="fa-solid fa-rotate-left"></i> CONFIRM RESET
            </button>
            <button onclick="closeModal('modal-reset')" class="btn btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<!-- ── MODAL: DELETE USER ────────────────────────────────────── -->
<div class="modal-overlay" id="modal-delete">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('modal-delete')">✕</button>
        <div class="modal-title" style="color:var(--red)"><i class="fa-solid fa-triangle-exclamation" style="margin-right:.5rem"></i>DELETE USER</div>
        <p id="delete-msg" style="color:var(--text2);font-size:.9rem;line-height:1.7;margin-bottom:1.25rem"></p>
        <div style="background:rgba(230,57,70,.08);border:1px solid rgba(230,57,70,.2);border-radius:6px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:var(--red)">
            <i class="fa-solid fa-circle-exclamation" style="margin-right:.4rem"></i>
            This is permanent. All bookings by this user will also be deleted.
        </div>
        <div style="display:flex;gap:.75rem">
            <button id="delete-confirm-btn" class="btn btn-red" style="flex:1;justify-content:center">
                <i class="fa-solid fa-trash"></i> DELETE ACCOUNT
            </button>
            <button onclick="closeModal('modal-delete')" class="btn btn-ghost">Cancel</button>
        </div>
    </div>
</div>

<div id="toast-container"></div>
<script>
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

// ── VIEW BOOKINGS ─────────────────────────────────────────────
function openBookingsModal(username, bookings) {
    document.getElementById('history-title').textContent = username.toUpperCase() + ' — BOOKING HISTORY';
    const body = document.getElementById('history-body');
    if (!bookings || bookings.length === 0) {
        body.innerHTML = '<p style="color:var(--text3);font-size:.85rem;text-align:center;padding:2rem">No bookings found for this player.</p>';
    } else {
        const colors = { scheduled:'#38bdf8', active:'#22c55e', completed:'#6b7280', cancelled:'#e63946' };
        body.innerHTML = bookings.map(b => `
            <div class="mini-booking-row">
                <div style="display:flex;align-items:center;gap:.75rem">
                    <div style="font-family:var(--font-head);font-size:.75rem;color:var(--red);min-width:55px">${b.code}</div>
                    <div>
                        <div style="font-weight:700;color:var(--text);font-size:.82rem">${b.date} · ${b.start}–${b.end}</div>
                        <div style="font-size:.72rem;color:var(--text3)">${b.zone} · ${b.dur}h</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem">
                    <span style="font-weight:700;color:var(--text)">RM ${b.cost}</span>
                    <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;padding:.15rem .5rem;border-radius:3px;
                        color:${colors[b.status]||'#6b7280'};background:${colors[b.status]||'#6b7280'}22;
                        border:1px solid ${colors[b.status]||'#6b7280'}44">${b.status}</span>
                </div>
            </div>
        `).join('');
    }
    openModal('modal-history');
}

// ── RESET MEMBERSHIP ──────────────────────────────────────────
function openResetModal(id, username, currentTier) {
    document.getElementById('reset-msg').textContent =
        `Reset ${username}'s membership from ${currentTier} back to Bronze? Their total gaming hours will be set to 0.`;
    document.getElementById('reset-confirm-btn').onclick = async () => {
        closeModal('modal-reset');
        const fd = new FormData();
        fd.append('action',  'reset_membership');
        fd.append('user_id', id);
        const data = await fetch('/try_harder_v3/api_admin.php', { method:'POST', body:fd }).then(r=>r.json());
        showToast(data.message || data.error, data.success ? 'success' : 'error');
        if (data.success) {
            // Update badge live without reload
            const badge = document.getElementById('badge-' + id);
            if (badge) { badge.textContent = 'Bronze'; badge.className = 'badge badge-gray'; }
        }
    };
    openModal('modal-reset');
}

// ── DELETE USER ───────────────────────────────────────────────
function openDeleteModal(id, username) {
    document.getElementById('delete-msg').textContent =
        `Permanently delete account "${username}"? This will remove the account and all their booking records.`;
    document.getElementById('delete-confirm-btn').onclick = async () => {
        closeModal('modal-delete');
        const fd = new FormData();
        fd.append('action',  'delete_user');
        fd.append('user_id', id);
        const data = await fetch('/try_harder_v3/api_admin.php', { method:'POST', body:fd }).then(r=>r.json());
        showToast(data.message || data.error, data.success ? 'success' : 'error');
        if (data.success) {
            // Remove row from table live
            const row = document.getElementById('user-row-' + id);
            if (row) row.style.cssText = 'opacity:0;transition:opacity .3s';
            setTimeout(() => row?.remove(), 350);
        }
    };
    openModal('modal-delete');
}
</script>
</body>
</html>