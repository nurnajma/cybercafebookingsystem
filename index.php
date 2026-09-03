<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

startSess();

// Already logged in → redirect based on role
$loggedIn = currentUser();
if ($loggedIn) {
    header('Location: ' . ($loggedIn['is_admin'] ? 'admin/dashboard.php' : 'dashboard.php'));
    exit;
}

// ── HANDLE LOGIN POST ─────────────────────────────────────────
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {

    // Fix 1: CSRF check
    verifyCsrf();

    // Fix 12: rate limit
    if (!checkLoginRateLimit()) {
        $loginError = 'Too many failed attempts. Please wait 10 minutes and try again.';
    } else {
        // Fix 5: sanitize input
        $username = trim(htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES));
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $loginError = 'Error: Please enter your username and passkey.';
        } else {
            $stmt = getDB()->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                recordFailedLogin();
                $loginError = 'Error: Invalid credentials. Please check your username and passkey.';
            } else {
                clearLoginAttempts();
                session_regenerate_id(true);
                $_SESSION['uid'] = $user['user_id'];
                header('Location: ' . ($user['is_admin'] ? 'admin/dashboard.php' : 'dashboard.php'));
                exit;
            }
        }
    }
}

// Live station counts for hero widget
$db     = getDB();
$counts = $db->query("SELECT status, COUNT(*) as c FROM stations GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$avail  = ($counts['available'] ?? 0);
$total  = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Portal — TryHarder PC Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;600;700&display=swap');
        body { font-family: 'Rajdhani', sans-serif; background-color: #06070a; background-image: radial-gradient(circle at 50% -20%, #1e113a 0%, #06070a 60%); }
        .font-cyber { font-family: 'Orbitron', sans-serif; }
        .glow-purple { box-shadow: 0 0 25px rgba(139, 92, 246, 0.25); }
        .glass-panel { background: rgba(15, 17, 26, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(139, 92, 246, 0.15); }
    </style>
</head>
<body class="text-gray-100 min-h-screen flex flex-col justify-between overflow-x-hidden">

    <header class="border-b border-purple-950/40 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-2.5 rounded-lg font-bold text-xl glow-purple animate-pulse">
                <i class="fa-solid fa-gamepad"></i>
            </div>
            <div>
                <span class="font-cyber text-lg md:text-xl font-bold tracking-wider text-white">TryHarder <span class="text-purple-500">PC Hub</span></span>
                <p class="text-[10px] text-purple-400 font-bold tracking-widest uppercase hidden sm:block">Level up your gaming experience</p>
            </div>
        </div>
        <div>
            <button onclick="openModal()" class="font-cyber text-xs bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold py-2.5 px-5 rounded-lg border border-purple-500/30 transition-all glow-purple hover:scale-105 active:scale-95 duration-200">
                <i class="fa-solid fa-right-to-bracket mr-2"></i>SIGN IN / LOG IN
            </button>
        </div>
    </header>

    <section class="relative py-12 md:py-20 px-6 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center flex-grow">
        <div class="space-y-6">
            <div class="inline-block bg-purple-500/10 border border-purple-500/30 text-purple-400 text-xs px-3 py-1 rounded-full uppercase tracking-widest font-cyber font-bold">
                <i class="fa-solid fa-university mr-1.5"></i> WELCOME TO OUR HUB!!
            </div>
            <h1 class="font-cyber text-4xl md:text-6xl font-black text-white leading-tight">
                ELIMINATE QUEUES.<br><span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-fuchsia-500 to-indigo-400">PLAY INSTANTLY.</span>
            </h1>
            <p class="text-gray-400 text-base md:text-lg max-w-lg leading-relaxed">
                Log into Malaysia's premier student-friendly hub. Check real-time seat states on our graphical floor plan, bypass scheduling conflicts, and earn rank tiers automatically.
            </p>
            <div class="flex flex-wrap gap-4 pt-2">
                <a href="register.php" class="font-cyber bg-purple-600 hover:bg-purple-500 text-white text-center font-bold px-7 py-4 rounded-lg text-xs tracking-widest transition-all glow-purple hover:scale-105 duration-200 flex items-center justify-center">
                    <i class="fa-solid fa-user-plus mr-2"></i> SIGN UP / CREATE ACCOUNT
                </a>
                <a href="#rates-specs" class="bg-slate-900 border border-slate-800 hover:bg-slate-800 text-gray-300 font-bold px-7 py-4 rounded-lg text-xs tracking-widest transition-all flex items-center justify-center">
                    VIEW HUB RATES
                </a>
            </div>
        </div>

        <!-- LIVE HERO WIDGET (real DB data) -->
        <div class="relative flex justify-center items-center">
            <div class="absolute w-80 h-80 bg-purple-600/10 rounded-full blur-3xl -top-10 -left-10"></div>
            <div class="absolute w-80 h-80 bg-indigo-600/15 rounded-full blur-3xl -bottom-10 -right-10"></div>
            <div class="relative glass-panel p-6 rounded-2xl w-full max-w-md shadow-2xl">
                <div class="flex justify-between items-center mb-5 pb-3 border-b border-slate-800/60">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                        <span class="text-xs uppercase font-cyber font-bold text-gray-300 tracking-wider">Live Station Health</span>
                    </div>
                    <span class="text-xs bg-purple-500/10 border border-purple-500/30 px-2.5 py-1 rounded text-purple-400 font-bold font-cyber">
                        <?= $avail ?> / <?= $total ?> Available
                    </span>
                </div>
                <div class="space-y-4">
                    <?php
                    $vipAvail = $db->query("SELECT COUNT(*) FROM stations WHERE zone='VIP' AND status='available'")->fetchColumn();
                    $stdAvail = $db->query("SELECT COUNT(*) FROM stations WHERE zone='Standard' AND status='available'")->fetchColumn();
                    ?>
                    <div class="flex justify-between items-center bg-slate-950/60 p-3.5 rounded-xl border border-slate-900/80">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 text-lg">
                                <i class="fa-solid fa-crown"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white font-cyber">VIP Stream Zone</h4>
                                <p class="text-xs text-gray-500">RTX 4090 • 360Hz C-Display</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold <?= $vipAvail > 0 ? 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20' : 'text-red-400 bg-red-500/10 border-red-500/20' ?> px-2.5 py-1 rounded-full border">
                            <?= $vipAvail ?> Open
                        </span>
                    </div>
                    <div class="flex justify-between items-center bg-slate-950/60 p-3.5 rounded-xl border border-slate-900/80">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-10 h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 text-lg">
                                <i class="fa-solid fa-desktop"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white font-cyber">Standard Arena</h4>
                                <p class="text-xs text-gray-500">RTX 4070 • 240Hz Display</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold <?= $stdAvail > 0 ? 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20' : 'text-red-400 bg-red-500/10 border-red-500/20' ?> px-2.5 py-1 rounded-full border">
                            <?= $stdAvail ?> Open
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- RATES SECTION (unchanged) -->
    <section id="rates-specs" class="bg-slate-950/50 border-t border-purple-950/20 py-16 px-6">
        <div class="max-w-7xl mx-auto space-y-12">
            <div class="text-center max-w-xl mx-auto space-y-3">
                <h2 class="font-cyber text-2xl md:text-3xl font-bold text-white">HARDWARE SPECS & HOURLY RATES</h2>
                <p class="text-gray-400 text-sm">Two performance tiers engineered for different gaming requirements.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 relative overflow-hidden">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-indigo-400 tracking-widest uppercase font-cyber">Standard Arena</span>
                            <h3 class="text-2xl font-bold text-white mt-1">THE ARENA RIG</h3>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-cyber font-extrabold text-white">RM 5</span>
                            <span class="text-xs text-gray-400 block">/ hour</span>
                        </div>
                    </div>
                    <ul class="space-y-3 text-xs text-gray-300 border-t border-slate-800 pt-4">
                        <li class="flex items-center"><i class="fa-solid fa-microchip text-indigo-400 mr-2.5 w-4"></i>AMD Ryzen 7 7700X</li>
                        <li class="flex items-center"><i class="fa-solid fa-cube text-indigo-400 mr-2.5 w-4"></i>NVIDIA GeForce RTX 4070</li>
                        <li class="flex items-center"><i class="fa-solid fa-memory text-indigo-400 mr-2.5 w-4"></i>16GB DDR5 5200MHz</li>
                        <li class="flex items-center"><i class="fa-solid fa-video text-indigo-400 mr-2.5 w-4"></i>27" 240Hz IPS Gaming Panel</li>
                    </ul>
                </div>
                <div class="bg-gradient-to-br from-slate-900 to-purple-950/30 border border-purple-500/30 rounded-2xl p-6 space-y-4 relative overflow-hidden glow-purple">
                    <div class="absolute top-3 right-3 text-[10px] bg-purple-600 text-white px-2 py-0.5 rounded font-cyber font-bold">
                        <i class="fa-solid fa-crown text-[9px]"></i> PREMIUM
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-purple-400 tracking-widest uppercase font-cyber">VIP Stream Zone</span>
                            <h3 class="text-2xl font-bold text-white mt-1">THE STREAMING ENGINE</h3>
                        </div>
                        <div class="text-right pt-6 sm:pt-0">
                            <span class="text-3xl font-cyber font-extrabold text-purple-400">RM 6</span>
                            <span class="text-xs text-gray-400 block">/ hour</span>
                        </div>
                    </div>
                    <ul class="space-y-3 text-xs text-gray-300 border-t border-purple-500/10 pt-4">
                        <li class="flex items-center"><i class="fa-solid fa-microchip text-purple-400 mr-2.5 w-4"></i>AMD Ryzen 9 7950X3D</li>
                        <li class="flex items-center"><i class="fa-solid fa-cube text-purple-400 mr-2.5 w-4"></i>NVIDIA GeForce RTX 4090</li>
                        <li class="flex items-center"><i class="fa-solid fa-memory text-purple-400 mr-2.5 w-4"></i>32GB DDR5 6000MHz Low Latency</li>
                        <li class="flex items-center"><i class="fa-solid fa-video text-purple-400 mr-2.5 w-4"></i>Professional 4K Studio Broadcast Setup</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- LOGIN MODAL (real PHP POST) -->
    <div id="authModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm px-4 <?= $loginError ? '' : 'hidden' ?>">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 md:p-8 max-w-md w-full relative glow-purple glass-panel">
            <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors p-1 rounded-md hover:bg-slate-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <div class="text-center mb-6">
                <h3 class="font-cyber text-xl font-bold tracking-wider text-white uppercase">PILOT SIGN IN</h3>
                <p class="text-xs text-gray-400 mt-1">Enter credentials to unlock terminal operations.</p>
            </div>

            <?php if ($loginError): ?>
            <div class="p-3 rounded-lg text-xs font-semibold mb-4 text-center bg-red-500/10 text-red-400 border border-red-500/30">
                <?= htmlspecialchars($loginError) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="space-y-4">
                <input type="hidden" name="action" value="login"/>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1.5">Username</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 text-gray-500"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="username" required placeholder="e.g. Adham_Yahya"
                               class="w-full bg-slate-950/80 border border-slate-800/80 rounded-lg p-3.5 pl-10 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1.5">Passkey</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 text-gray-500"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" required placeholder="••••••••"
                               class="w-full bg-slate-950/80 border border-slate-800/80 rounded-lg p-3.5 pl-10 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                    </div>
                </div>
                <button type="submit" class="w-full font-cyber bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold py-3.5 rounded-lg text-xs tracking-widest transition-all glow-purple">
                    VERIFY IDENTITY & CONNECT
                </button>
            </form>
            <div class="border-t border-slate-800 mt-6 pt-4 text-center">
                <p class="text-xs text-gray-400">New system gamer? <a href="register.php" class="text-purple-400 hover:underline font-bold">Register Profile Here</a></p>
            </div>
        </div>
    </div>

    <footer class="border-t border-purple-950/20 bg-slate-950/80 text-center py-8 px-6 text-xs text-gray-500">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-left space-y-1">
                <p>&copy; 2026 TryHarder PC Hub.</p>
            </div>
        </div>
    </footer>

    <script>
        function openModal()  { document.getElementById('authModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('authModal').classList.add('hidden'); }
    </script>
</body>
</html>