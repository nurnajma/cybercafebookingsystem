<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/avatars.php';

$user     = requireUser();
$db       = getDB();
$stations = $db->query("SELECT * FROM stations ORDER BY zone DESC, station_code ASC")->fetchAll();
$vipStations = array_filter($stations, fn($s) => $s['zone'] === 'VIP');
$stdStations = array_filter($stations, fn($s) => $s['zone'] === 'Standard');
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $user['full_name']), 0, 2)));
$mColor   = membershipColor($user['membership']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking — TryHarder PC Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;600;700&display=swap');
        body { font-family: 'Rajdhani', sans-serif; background-color: #08090c; }
        .font-cyber { font-family: 'Orbitron', sans-serif; }
        .glow-purple { box-shadow: 0 0 25px rgba(139, 92, 246, 0.35); }
        .glow-emerald { box-shadow: 0 0 15px rgba(16, 185, 129, 0.25); }
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
            <a href="dashboard.php" class="hover:text-purple-400 transition-colors">Dashboard</a>
            <a href="booking.php"   class="text-purple-400 hover:text-purple-300 transition-colors">Book Station</a>
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

    <main class="flex-grow p-6 max-w-7xl mx-auto w-full space-y-6">

        <div class="flex flex-col md:flex-row md:justify-between md:items-center pb-6 border-b border-slate-800 gap-4">
            <div>
                <h2 class="font-cyber text-2xl font-bold text-white tracking-wide flex items-center">
                    <i class="fa-solid fa-map-location-dot mr-3 text-purple-500"></i>Interactive Floor Seating Map
                </h2>
                <p class="text-xs md:text-sm text-gray-400">Click an available green terminal node to automatically map it to your booking schedule form.</p>
            </div>
            <div class="flex flex-wrap gap-4 text-xs bg-slate-950 p-3 rounded-lg border border-slate-800/80">
                <div class="flex items-center space-x-2">
                    <span class="w-3.5 h-3.5 bg-emerald-500 rounded glow-emerald"></span>
                    <span class="text-gray-300 font-semibold">Available</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-3.5 h-3.5 bg-red-600/50 rounded"></span>
                    <span class="text-gray-400 font-semibold">Occupied</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-3.5 h-3.5 bg-yellow-500/50 rounded"></span>
                    <span class="text-gray-400 font-semibold">Maintenance</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-3.5 h-3.5 bg-purple-600 rounded glow-purple"></span>
                    <span class="text-purple-300 font-semibold">Selected</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- FLOOR MAP -->
            <div class="lg:col-span-2 bg-slate-950 p-6 rounded-xl border border-slate-800 flex flex-col gap-8 min-h-[450px]">

                <!-- VIP ZONE -->
                <div>
                    <div class="text-center mb-4">
                        <span class="font-cyber text-xs font-bold text-amber-500 uppercase tracking-widest bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full">
                            <i class="fa-solid fa-crown mr-1"></i> VIP ZONE (Liquid Cooling Specs) — RM6/hr
                        </span>
                    </div>
                    <div class="grid grid-cols-5 gap-3 md:gap-4">
                        <?php foreach ($vipStations as $s): ?>
                        <?php if ($s['status'] === 'available'): ?>
                        <button onclick="selectStation('<?= $s['station_code'] ?>','<?= $s['station_id'] ?>','VIP',<?= $s['rate_per_hour'] ?>)"
                            id="node-<?= $s['station_code'] ?>"
                            class="station-node bg-slate-900 border border-emerald-500/30 p-3 rounded-lg flex flex-col items-center hover:border-purple-500 hover:scale-105 transition-all glow-emerald">
                            <i class="fa-solid fa-desktop text-emerald-400 text-lg mb-1"></i>
                            <span class="text-[10px] font-cyber font-bold text-white"><?= $s['station_code'] ?></span>
                        </button>
                        <?php elseif ($s['status'] === 'maintenance'): ?>
                        <div class="bg-slate-900 border border-yellow-950/60 p-3 rounded-lg flex flex-col items-center opacity-40 cursor-not-allowed" title="Under Maintenance">
                            <i class="fa-solid fa-wrench text-yellow-600 text-lg mb-1"></i>
                            <span class="text-[10px] font-cyber font-bold text-gray-500"><?= $s['station_code'] ?></span>
                        </div>
                        <?php else: ?>
                        <div class="bg-slate-900 border border-red-950/60 p-3 rounded-lg flex flex-col items-center opacity-40 cursor-not-allowed">
                            <i class="fa-solid fa-desktop text-red-600 text-lg mb-1"></i>
                            <span class="text-[10px] font-cyber font-bold text-gray-500"><?= $s['station_code'] ?></span>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- STANDARD ZONE -->
                <div>
                    <div class="text-center mb-4">
                        <span class="font-cyber text-xs font-bold text-indigo-400 uppercase tracking-widest bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-full">
                            <i class="fa-solid fa-users mr-1"></i> Standard Zone — RM5/hr
                        </span>
                    </div>
                    <div class="grid grid-cols-5 gap-3 md:gap-4">
                        <?php foreach ($stdStations as $s): ?>
                        <?php if ($s['status'] === 'available'): ?>
                        <button onclick="selectStation('<?= $s['station_code'] ?>','<?= $s['station_id'] ?>','Standard',<?= $s['rate_per_hour'] ?>)"
                            id="node-<?= $s['station_code'] ?>"
                            class="station-node bg-slate-900 border border-emerald-500/30 p-3 rounded-lg flex flex-col items-center hover:border-purple-500 hover:scale-105 transition-all glow-emerald">
                            <i class="fa-solid fa-desktop text-emerald-400 text-lg mb-1"></i>
                            <span class="text-[10px] font-cyber font-bold text-white"><?= $s['station_code'] ?></span>
                        </button>
                        <?php elseif ($s['status'] === 'maintenance'): ?>
                        <div class="bg-slate-900 border border-yellow-950/60 p-3 rounded-lg flex flex-col items-center opacity-40 cursor-not-allowed" title="Under Maintenance">
                            <i class="fa-solid fa-wrench text-yellow-600 text-lg mb-1"></i>
                            <span class="text-[10px] font-cyber font-bold text-gray-500"><?= $s['station_code'] ?></span>
                        </div>
                        <?php else: ?>
                        <div class="bg-slate-900 border border-red-950/60 p-3 rounded-lg flex flex-col items-center opacity-40 cursor-not-allowed">
                            <i class="fa-solid fa-desktop text-red-600 text-lg mb-1"></i>
                            <span class="text-[10px] font-cyber font-bold text-gray-500"><?= $s['station_code'] ?></span>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ENTRANCE -->
                <div class="text-center text-[10px] text-gray-600 font-cyber tracking-widest border-t border-slate-900 pt-4">
                    <i class="fa-solid fa-door-open mr-1"></i> GROUND ENTRANCE & LOGISTICS COUNTER
                </div>
            </div>

            <!-- BOOKING FORM -->
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-xl flex flex-col justify-between shadow-xl">
                <div>
                    <h3 class="font-cyber text-lg font-bold text-white mb-4 border-b border-slate-800 pb-2 flex items-center">
                        <i class="fa-regular fa-calendar-check mr-2 text-purple-400"></i>Configuration Console
                    </h3>

                    <div id="formError" class="hidden p-3 rounded-lg text-xs font-semibold mb-3 bg-red-500/10 text-red-400 border border-red-500/30"></div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Target Station Node</label>
                            <input id="formStation" type="text" readonly value="None Selected"
                                class="w-full bg-slate-950 border border-slate-800 text-purple-300 font-cyber font-bold px-3 py-2 rounded-lg outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Operating Zone</label>
                            <input id="formZone" type="text" readonly value="None"
                                class="w-full bg-slate-950 border border-slate-800 text-gray-400 px-3 py-2 rounded-lg outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Select Date</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-purple-400 pointer-events-none z-10">
                                    <i class="fa-regular fa-calendar"></i>
                                </span>
                                <input id="formDate" type="text" readonly required placeholder="Pick a date..."
                                    class="w-full bg-slate-950 border border-slate-800 text-white pl-9 pr-3 py-2 rounded-lg focus:outline-none focus:border-purple-500 transition-colors text-sm cursor-pointer"
                                    style="caret-color:transparent">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Launch Time</label>
                                <input id="formTime" type="time" required
                                    class="w-full bg-slate-950 border border-slate-800 text-white px-3 py-2 rounded-lg focus:outline-none focus:border-purple-500 transition-colors text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Duration</label>
                                <select id="formDuration" onchange="updateCost()"
                                    class="w-full bg-slate-950 border border-slate-800 text-white px-3 py-2 rounded-lg focus:outline-none focus:border-purple-500 transition-colors text-sm">
                                    <option value="1">1 Hour</option>
                                    <option value="2" selected>2 Hours</option>
                                    <option value="4">4 Hours</option>
                                    <option value="8">Full Day (8hrs)</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-purple-950/20 border border-purple-500/20 p-3 rounded-lg text-xs text-purple-300">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Estimated Cost: <span id="costPreview" class="font-cyber font-bold text-white">Select station first</span>
                        </div>

                        <div class="bg-purple-950/20 border border-purple-500/20 p-3 rounded-lg text-xs text-purple-300">
                            <i class="fa-solid fa-shield-halved mr-1"></i> Conflict Checker: Our booking engine validates timestamps to prevent simultaneous multi-user seat collisions.
                        </div>

                        <button onclick="submitBooking()"
                            class="w-full mt-4 font-cyber bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold py-3 rounded-lg text-xs tracking-wider transition-all glow-purple">
                            SUBMIT RESERVATION BLOCK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- CONFIRMATION MODAL -->
    <div id="confirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm px-4 hidden">
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 max-w-sm w-full text-center glow-purple">
            <div class="text-emerald-500 text-4xl mb-3"><i class="fa-regular fa-circle-check"></i></div>
            <h3 class="font-cyber text-xl font-bold text-white">RESERVATION SECURED</h3>
            <p class="text-xs text-gray-400 mt-2">No conflicting blocks detected. Your station allocation has been cataloged.</p>
            <div class="bg-slate-950 border border-slate-800/80 rounded p-3 my-4 text-left space-y-1 text-xs">
                <p class="text-gray-400">Terminal: <span id="confirmNode" class="text-purple-400 font-bold font-cyber"></span></p>
                <p class="text-gray-400">Schedule: <span id="confirmTime" class="text-white"></span></p>
                <p class="text-gray-400">Total: <span id="confirmCost" class="text-emerald-400 font-bold"></span></p>
            </div>
            <button onclick="window.location.href='dashboard.php'"
                class="w-full font-cyber bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 rounded text-xs tracking-wider transition-colors">
                VIEW IN DASHBOARD
            </button>
        </div>
    </div>

    <footer class="border-t border-slate-900 bg-slate-950/80 text-center py-8 px-6 text-xs text-gray-500">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-left">
                <p>&copy; 2026 TryHarder PC Hub</p>
            </div>
        </div>
    </footer>

    <script>
        let selectedStationId = null;
        let selectedRate      = 0;

        function selectStation(code, id, zone, rate) {
            document.querySelectorAll('.station-node').forEach(n => {
                n.classList.remove('border-purple-500','bg-purple-950/20','glow-purple');
                n.classList.add('glow-emerald');
            });
            const node = document.getElementById('node-' + code);
            if (node) {
                node.classList.remove('glow-emerald');
                node.classList.add('border-purple-500','bg-purple-950/20','glow-purple');
            }
            selectedStationId = id;
            selectedRate      = parseFloat(rate);
            document.getElementById('formStation').value = code;
            document.getElementById('formZone').value    = zone + ' Class Unit';
            updateCost();
        }

        function updateCost() {
            if (!selectedRate) return;
            const dur  = parseFloat(document.getElementById('formDuration').value);
            const cost = (dur * selectedRate).toFixed(2);
            document.getElementById('costPreview').textContent = dur + 'h × RM' + selectedRate.toFixed(2) + ' = RM' + cost;
        }

        async function submitBooking() {
            const station  = document.getElementById('formStation').value;
            const date     = document.getElementById('formDate').value;
            const time     = document.getElementById('formTime').value;
            const duration = document.getElementById('formDuration').value;
            const errBox   = document.getElementById('formError');

            if (station === 'None Selected') {
                errBox.textContent = 'Protocol Error: Please select an available station node.';
                errBox.classList.remove('hidden'); return;
            }
            if (!date || !time) {
                errBox.textContent = 'Protocol Error: Please fill in the date and launch time.';
                errBox.classList.remove('hidden'); return;
            }
            errBox.classList.add('hidden');

            const fd = new FormData();
            fd.append('action',     'create');
            fd.append('station_id', selectedStationId);
            fd.append('date',       date);
            fd.append('start_time', time);
            fd.append('duration',   duration);

            const res  = await fetch('api_booking.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.error) {
                errBox.textContent = data.error;
                errBox.classList.remove('hidden');
                return;
            }

            document.getElementById('confirmNode').textContent = station;
            document.getElementById('confirmTime').textContent = date + ' @ ' + time;
            document.getElementById('confirmCost').textContent = 'RM ' + data.total_cost;
            document.getElementById('confirmationModal').classList.remove('hidden');
        }
    </script>

    <!-- Flatpickr Calendar -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/themes/dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <style>
        /* Override Flatpickr to match gaming dark theme */
        .flatpickr-calendar {
            background: #0f0f1a !important;
            border: 1px solid #2a2a4a !important;
            border-radius: 10px !important;
            box-shadow: 0 0 30px rgba(139,92,246,.25) !important;
            font-family: 'Rajdhani', sans-serif !important;
        }
        .flatpickr-months {
            background: #13131f !important;
            border-radius: 10px 10px 0 0 !important;
            padding: .4rem 0 !important;
        }
        .flatpickr-month { color: #e8e8f0 !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: #13131f !important;
            color: #e8e8f0 !important;
            font-weight: 700 !important;
        }
        .flatpickr-current-month input.cur-year {
            color: #e8e8f0 !important;
            font-weight: 700 !important;
        }
        .flatpickr-prev-month svg, .flatpickr-next-month svg { fill: #9090b0 !important; }
        .flatpickr-prev-month:hover svg, .flatpickr-next-month:hover svg { fill: #a855f7 !important; }
        .flatpickr-weekdays { background: #13131f !important; }
        span.flatpickr-weekday {
            background: #13131f !important;
            color: #5a5a8a !important;
            font-weight: 700 !important;
            font-size: .72rem !important;
            letter-spacing: 1px !important;
        }
        .flatpickr-days { border-color: #2a2a4a !important; }
        .flatpickr-day {
            color: #9090b0 !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
        }
        .flatpickr-day:hover {
            background: rgba(168,85,247,.2) !important;
            border-color: rgba(168,85,247,.4) !important;
            color: #e8e8f0 !important;
        }
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            background: linear-gradient(135deg, #7c3aed, #4f46e5) !important;
            border-color: transparent !important;
            color: #fff !important;
            box-shadow: 0 0 12px rgba(139,92,246,.5) !important;
        }
        .flatpickr-day.today {
            border-color: #a855f7 !important;
            color: #a855f7 !important;
        }
        .flatpickr-day.today:hover {
            background: rgba(168,85,247,.2) !important;
            color: #e8e8f0 !important;
        }
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #2a2a4a !important;
            cursor: not-allowed !important;
        }
        .numInputWrapper span { border-color: #2a2a4a !important; }
        .numInputWrapper span svg { fill: #9090b0 !important; }
        .numInputWrapper:hover { background: rgba(139,92,246,.1) !important; }
    </style>
    <script>
        // Initialise Flatpickr on the date input
        flatpickr('#formDate', {
            minDate:     'today',
            maxDate:     new Date().fp_incr(60),  // allow booking up to 60 days ahead
            dateFormat:  'Y-m-d',                 // sends YYYY-MM-DD to the API
            altInput:    true,
            altFormat:   'D, d M Y',              // displays as "Mon, 29 May 2026"
            disableMobile: true,                  // always use custom calendar on mobile too
            locale: {
                firstDayOfWeek: 1                 // week starts Monday
            },
            onChange: function(selectedDates, dateStr) {
                // Trigger cost update when date changes
                updateCost();
            }
        });
    </script>
</body>
</html>