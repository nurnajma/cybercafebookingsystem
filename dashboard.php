<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/avatars.php';

$user = requireUser();
$db   = getDB();

// Fetch bookings with station info
$stmt = $db->prepare("
    SELECT b.*, s.station_code, s.zone, s.specs
    FROM bookings b
    JOIN stations s ON s.station_id = b.station_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user['user_id']]);
$bookings = $stmt->fetchAll();

$totalHours  = $user['total_hours'];
$completed   = count(array_filter($bookings, fn($b) => $b['status'] === 'completed'));
$scheduled   = count(array_filter($bookings, fn($b) => $b['status'] === 'scheduled'));

// Membership tier progress
$tiers = ['Bronze'=>0,'Silver'=>50,'Gold'=>200,'Platinum'=>500];
$tierKeys = array_keys($tiers);
$curIdx   = array_search($user['membership'], $tierKeys);
$nextTier = $tierKeys[min($curIdx + 1, count($tierKeys) - 1)];
$curMin   = $tiers[$user['membership']];
$nextMin  = $tiers[$nextTier];
$pct      = $curIdx >= count($tierKeys)-1 ? 100 : min(100, round((($totalHours - $curMin) / ($nextMin - $curMin)) * 100));
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $user['full_name']), 0, 2)));
$mColor   = membershipColor($user['membership']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — TryHarder PC Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/themes/dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;600;700&display=swap');
        body { font-family: 'Rajdhani', sans-serif; background-color: #08090c; }
        .font-cyber { font-family: 'Orbitron', sans-serif; }
        .glow-purple { box-shadow: 0 0 25px rgba(139, 92, 246, 0.3); }
        .glow-gold   { box-shadow: 0 0 25px rgba(245, 158, 11, 0.2); }
    </style>
</head>
<body class="text-gray-100 min-h-screen flex flex-col justify-between">

    <header class="border-b border-purple-900/30 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-purple-600 text-black p-2 rounded-md font-bold text-xl glow-purple">
                <i class="fa-solid fa-gamepad"></i>
            </div>
            <div>
                <span class="font-cyber text-lg md:text-xl font-bold tracking-wider text-white">TryHarder <span class="text-purple-500">PC Hub</span></span>
                <p class="text-[10px] text-gray-400 italic hidden sm:block">Level up your gaming experience</p>
            </div>
        </div>
        <nav class="hidden md:flex space-x-6 text-sm font-semibold tracking-wider uppercase">
            <a href="dashboard.php" class="text-purple-400 hover:text-purple-300 transition-colors">Dashboard</a>
			<a href="booking.php"   class="hover:text-purple-400 transition-colors">Book Station</a>
            <?php if ($user['is_admin']): ?>
            <a href="admin.php"     class="hover:text-purple-400 transition-colors">Management</a>
            <?php endif; ?>
        </nav>
        <div class="flex items-center space-x-3">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-white"><?= htmlspecialchars($user['username']) ?></p>
                <span class="text-[10px] px-2 py-0.5 rounded-full border <?= $mColor ?>"><?= $user['membership'] ?> Tier</span>
            </div>
            <div class="w-10 h-10 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-cyber font-bold text-white border border-purple-400/40 glow-purple">
                <?= renderAvatar($user['avatar_key'] ?? 'gamepad', 'sm') ?>
            </div>
            <form method="POST" action="logout.php" style="display:inline">
                <button type="submit" class="text-xs border border-slate-700 bg-slate-900 hover:bg-slate-800 text-gray-400 font-semibold py-2 px-3 rounded-lg tracking-wider transition-all">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </header>

    <main class="flex-grow p-6 max-w-7xl mx-auto w-full space-y-8">

        <!-- PROFILE ROW -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <?= renderAvatar($user['avatar_key'] ?? 'gamepad', 'lg', 'box-shadow:0 0 20px rgba(139,92,246,.35);') ?>
                    <button onclick="openAvatarPicker()"
                        title="Change Avatar"
                        style="position:absolute;bottom:-6px;right:-6px;width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#4f46e5);border:2px solid #08090c;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.55rem;color:#fff;">
                        ✏️
                    </button>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white font-cyber"><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p class="text-sm text-gray-400">
                        <?= $user['is_admin'] ? 'Administrator Account' : 'Member Account' ?> •
                        <span class="text-purple-400 font-bold">@<?= htmlspecialchars($user['username']) ?></span>
                    </p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="booking.php" class="font-cyber bg-purple-600 hover:bg-purple-500 text-white font-bold py-2.5 px-4 rounded-lg text-xs tracking-wider transition-all glow-purple">
                    <i class="fa-solid fa-plus mr-1"></i> NEW BOOKING
                </a>
                
            </div>
        </div>

        <!-- LOYALTY PROGRESS & STATS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-gradient-to-br from-slate-900 to-purple-950/20 p-6 rounded-2xl border border-purple-500/20 flex flex-col justify-between shadow-lg">
                <div>
                    <div class="flex justify-between items-start flex-wrap gap-2">
                        <div>
                            <h3 class="text-gray-400 text-xs font-bold uppercase tracking-widest">Membership Progress Tracker</h3>
                            <p class="font-cyber text-2xl font-black text-amber-500 mt-1">
                                <i class="fa-solid fa-medal mr-2"></i><?= strtoupper($user['membership']) ?> MEMBER
                            </p>
                        </div>
                        <?php if ($user['membership'] !== 'Platinum'): ?>
                        <span class="text-xs bg-purple-950 text-purple-300 border border-purple-500/20 px-3 py-1 rounded-full font-bold">
                            <?= $nextMin - $totalHours ?> hrs to <?= $nextTier ?>
                        </span>
                        <?php else: ?>
                        <span class="text-xs bg-purple-950 text-purple-300 border border-purple-500/20 px-3 py-1 rounded-full font-bold">
                            MAX TIER REACHED 💎
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-400 text-sm mt-3">
                        <?php if ($user['membership'] !== 'Platinum'): ?>
                            Reach <strong><?= $nextMin ?> gaming hours</strong> to achieve <strong><?= $nextTier ?> Status</strong> and unlock exclusive discounts and advanced reservation priorities.
                        <?php else: ?>
                            You've reached the highest tier! Enjoy all exclusive Platinum perks.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mt-6">
                    <div class="flex justify-between text-xs font-bold text-gray-300 mb-1.5">
                        <span><?= number_format($totalHours, 1) ?> Hours (Current)</span>
                        <span><?= $nextMin ?> Hours (<?= $nextTier ?> Lock)</span>
                    </div>
                    <div class="w-full bg-slate-950 h-3.5 rounded-full overflow-hidden p-0.5 border border-slate-800">
                        <div class="bg-gradient-to-r from-amber-500 via-purple-600 to-pink-500 h-full rounded-full shadow-inner" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900/60 p-6 rounded-2xl border border-slate-800 flex flex-col justify-between shadow-lg">
                <div class="flex justify-between items-center">
                    <h4 class="font-cyber text-xs font-bold uppercase text-gray-400 tracking-wider">Account Analytics</h4>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <div class="space-y-4 my-4">
                    <div class="flex justify-between items-center border-b border-slate-800/60 pb-2">
                        <span class="text-gray-400 text-sm">Accumulated Playtime</span>
                        <span class="font-cyber font-bold text-white"><?= number_format($totalHours, 1) ?> hrs</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-800/60 pb-2">
                        <span class="text-gray-400 text-sm">Completed Sessions</span>
                        <span class="font-cyber font-bold text-white"><?= $completed ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm">Pending Bookings</span>
                        <span class="font-cyber font-bold text-purple-400"><?= $scheduled ?> Scheduled</span>
                    </div>
                </div>
                <div class="bg-slate-950 p-2.5 rounded border border-slate-800/60 text-[11px] text-gray-500 flex items-center">
                    <i class="fa-solid fa-circle-info mr-2 text-indigo-400"></i> Stats updated upon session completion.
                </div>
            </div>
        </div>

        <!-- BOOKING HISTORY TABLE -->
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <h3 class="font-cyber text-lg font-bold text-white mb-4 flex items-center justify-between">
                <span><i class="fa-solid fa-list-check mr-2 text-purple-400"></i>Session Register & Logs</span>
                <a href="booking.php" class="text-xs bg-purple-600 hover:bg-purple-500 text-white font-bold py-1.5 px-3 rounded-lg transition-colors">+ New</a>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 text-gray-400 uppercase text-[11px] tracking-wider">
                            <th class="pb-3">Station ID</th>
                            <th class="pb-3">Zone</th>
                            <th class="pb-3">Session Date</th>
                            <th class="pb-3">Logged Block</th>
                            <th class="pb-3">Hours</th>
                            <th class="pb-3">Cost</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/50">
                        <?php if (!$bookings): ?>
                        <tr><td colspan="8" class="py-8 text-center text-gray-500">
                            No bookings yet. <a href="booking.php" class="text-purple-400 hover:underline">Book a station!</a>
                        </td></tr>
                        <?php else: foreach ($bookings as $b):
                            $badgeClass = match($b['status']) {
                                'scheduled' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                'active'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'completed' => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
                                'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                default     => 'bg-slate-500/10 text-gray-400 border-slate-500/20',
                            };
                        ?>
                        <tr>
                            <td class="py-3 font-bold text-purple-400 font-cyber"><?= htmlspecialchars($b['station_code']) ?></td>
                            <td class="py-3 text-gray-400"><?= $b['zone'] ?></td>
                            <td class="py-3 text-gray-300"><?= date('M d, Y', strtotime($b['booking_date'])) ?></td>
                            <td class="py-3 text-gray-300"><?= substr($b['start_time'],0,5) ?> – <?= substr($b['end_time'],0,5) ?></td>
                            <td class="py-3 text-gray-300"><?= $b['duration_hrs'] ?> hrs</td>
                            <td class="py-3 font-bold text-white">RM <?= number_format($b['total_cost'],2) ?></td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded text-xs border <?= $badgeClass ?>">
                                    <?= ucfirst($b['status']) ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <?php if ($b['status'] === 'scheduled'): ?>
                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                    <button onclick="openEditModal(<?= $b['booking_id'] ?>,'<?= $b['booking_date'] ?>','<?= substr($b['start_time'],0,5) ?>','<?= $b['duration_hrs'] ?>')"
                                        class="text-xs text-amber-400 hover:text-amber-300 border border-amber-500/20 hover:border-amber-500/50 hover:bg-amber-500/10 px-2 py-0.5 rounded transition-colors">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i>Edit
                                    </button>
                                    <button onclick="cancelBooking(<?= $b['booking_id'] ?>)"
                                        class="text-xs text-red-400 hover:text-red-300 border border-red-500/20 hover:border-red-500/50 px-2 py-0.5 rounded transition-colors">
                                        Cancel
                                    </button>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-600 text-xs">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-900 bg-slate-950/80 text-center py-8 px-6 text-xs text-gray-500">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-left">
                <p>&copy; 2026 TryHarder PC Hub.</p>
            </div>
        </div>
    </footer>

    <?= renderAvatarPickerModal($user['avatar_key'] ?? 'gamepad', 'api_avatar.php') ?>

    <!-- EDIT BOOKING MODAL -->
    <div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);backdrop-filter:blur(4px);z-index:500;align-items:center;justify-content:center;">
        <div style="background:#13131f;border:1px solid #2a2a4a;border-radius:12px;padding:1.75rem;width:90%;max-width:440px;position:relative">
            <button onclick="closeEditModal()" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:#5a5a7a;font-size:1.2rem;cursor:pointer">✕</button>
            <div style="font-family:Orbitron,monospace;font-size:.85rem;letter-spacing:2px;color:#fbbf24;margin-bottom:1.5rem">
                ✏️ EDIT BOOKING
            </div>
            <input type="hidden" id="edit-booking-id"/>

            <!-- Date -->
            <div style="margin-bottom:1rem">
                <label style="display:block;font-size:.72rem;letter-spacing:1px;color:#9090b0;text-transform:uppercase;font-weight:600;margin-bottom:.4rem">New Date</label>
                <div style="position:relative">
                    <span style="position:absolute;left:.75rem;top:.65rem;color:#a855f7;pointer-events:none;z-index:1">
                        <i class="fa-regular fa-calendar"></i>
                    </span>
                    <input type="text" id="edit-date" readonly placeholder="Pick a date..."
                        style="width:100%;background:#0d0d10;border:1px solid #2a2a4a;border-radius:6px;padding:.6rem .75rem .6rem 2.2rem;color:#e8e8f0;font-size:.9rem;outline:none;cursor:pointer;font-family:Rajdhani,sans-serif">
                </div>
            </div>

            <!-- Start time + Duration -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem">
                <div>
                    <label style="display:block;font-size:.72rem;letter-spacing:1px;color:#9090b0;text-transform:uppercase;font-weight:600;margin-bottom:.4rem">Start Time</label>
                    <select id="edit-start" onchange="updateEditCost()"
                        style="width:100%;background:#0d0d10;border:1px solid #2a2a4a;border-radius:6px;padding:.6rem .75rem;color:#e8e8f0;font-size:.9rem;outline:none;cursor:pointer;font-family:Rajdhani,sans-serif">
                        <?php for($h=10;$h<=22;$h++) echo "<option>" . sprintf('%02d:00',$h) . "</option>"; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.72rem;letter-spacing:1px;color:#9090b0;text-transform:uppercase;font-weight:600;margin-bottom:.4rem">Duration</label>
                    <select id="edit-duration" onchange="updateEditCost()"
                        style="width:100%;background:#0d0d10;border:1px solid #2a2a4a;border-radius:6px;padding:.6rem .75rem;color:#e8e8f0;font-size:.9rem;outline:none;cursor:pointer;font-family:Rajdhani,sans-serif">
                        <option value="1">1 Hour</option>
                        <option value="2">2 Hours</option>
                        <option value="3">3 Hours</option>
                        <option value="4">4 Hours</option>
                        <option value="5">5 Hours</option>
                        <option value="6">6 Hours</option>
                        <option value="8">8 Hours</option>
                    </select>
                </div>
            </div>

            <!-- Cost preview -->
            <div id="edit-cost-preview"
                style="background:rgba(168,85,247,.07);border:1px solid rgba(168,85,247,.2);border-radius:6px;padding:.65rem 1rem;margin-bottom:1.25rem;font-family:'Share Tech Mono',monospace;font-size:.85rem;color:#a855f7">
                Select options above
            </div>

            <div id="edit-error" style="display:none;background:rgba(230,57,70,.1);border:1px solid rgba(230,57,70,.3);border-radius:6px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#e63946"></div>

            <div style="display:flex;gap:.75rem">
                <button onclick="submitEdit()"
                    style="flex:1;background:linear-gradient(135deg,#f59e0b,#d97706);color:#000;border:none;border-radius:6px;padding:.7rem;font-family:Orbitron,monospace;font-size:.72rem;font-weight:700;letter-spacing:1.5px;cursor:pointer">
                    SAVE CHANGES
                </button>
                <button onclick="closeEditModal()"
                    style="background:none;border:1px solid #2a2a4a;border-radius:6px;padding:.7rem 1.2rem;color:#9090b0;font-size:.85rem;cursor:pointer">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
    async function cancelBooking(id) {
        if (!confirm('Cancel this booking?')) return;
        const fd = new FormData();
        fd.append('action', 'cancel');
        fd.append('booking_id', id);
        const res  = await fetch('api_booking.php', { method: 'POST', body: fd });
        const data = await res.json();
        alert(data.message || data.error);
        if (data.success) location.reload();
    }

    // ── EDIT BOOKING ─────────────────────────────────────────
    let editFlatpickr = null;

    function openEditModal(id, date, startTime, duration) {
        document.getElementById('edit-booking-id').value = id;
        document.getElementById('edit-start').value      = startTime;

        // Set duration dropdown
        const dur = document.getElementById('edit-duration');
        for (let i = 0; i < dur.options.length; i++) {
            if (parseFloat(dur.options[i].value) === parseFloat(duration)) {
                dur.selectedIndex = i; break;
            }
        }

        // Init or update Flatpickr
        if (editFlatpickr) {
            editFlatpickr.setDate(date, false);
        } else {
            editFlatpickr = flatpickr('#edit-date', {
                minDate:       'today',
                maxDate:       new Date().fp_incr(60),
                dateFormat:    'Y-m-d',
                altInput:      true,
                altFormat:     'D, d M Y',
                disableMobile: true,
                defaultDate:   date,
                onChange:      () => updateEditCost(),
            });
        }

        document.getElementById('edit-error').style.display = 'none';
        updateEditCost();
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function updateEditCost() {
        const dur = parseFloat(document.getElementById('edit-duration').value);
        // We don't know the rate here, so show duration only
        document.getElementById('edit-cost-preview').textContent =
            dur + 'h session · cost will be recalculated based on station rate';
    }

    async function submitEdit() {
        const id       = document.getElementById('edit-booking-id').value;
        const date     = document.getElementById('edit-date').value;
        const start    = document.getElementById('edit-start').value;
        const duration = document.getElementById('edit-duration').value;
        const errBox   = document.getElementById('edit-error');

        if (!date || !start || !duration) {
            errBox.textContent = 'Please fill in all fields.';
            errBox.style.display = 'block'; return;
        }
        errBox.style.display = 'none';

        const fd = new FormData();
        fd.append('action',     'edit');
        fd.append('booking_id', id);
        fd.append('date',       date);
        fd.append('start_time', start);
        fd.append('duration',   duration);

        const res  = await fetch('api_booking.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.error) {
            errBox.textContent = data.error;
            errBox.style.display = 'block'; return;
        }

        closeEditModal();
        alert('✅ ' + data.message);
        location.reload();
    }

    // Close modal on overlay click
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    </script>
</body>
</html>