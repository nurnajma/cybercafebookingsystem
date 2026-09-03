<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

startSess();
if (currentUser()) { header('Location: dashboard.php'); exit; }

$errors = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fullName = trim(htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES));
    $username = trim($_POST['username']  ?? '');
    $password = $_POST['password'] ?? '';

    if (!$fullName || !$username || !$password) {
        $errors = 'Error: All fields are required.';
    } elseif (strlen($password) < 6) {
        $errors = 'Error: Secure passkey must be at least 6 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors = 'Error: Username must be 3–30 characters, letters/numbers/underscores only.';
    } else {
        $db  = getDB();
        $chk = $db->prepare("SELECT user_id FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            $errors = 'Error: Username is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare("INSERT INTO users (full_name, username, password) VALUES (?,?,?)");
            $ins->execute([$fullName, $username, $hash]);
            $uid = $db->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['uid'] = $uid;
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration — TryHarder PC Hub</title>
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
        <a href="index.php" class="font-cyber text-xs border border-purple-500/30 bg-slate-900/80 hover:bg-slate-800 text-gray-300 font-bold py-2.5 px-5 rounded-lg transition-all hover:scale-105 active:scale-95 duration-200">
            <i class="fa-solid fa-chevron-left mr-2"></i>BACK TO HOME
        </a>
    </header>

    <main class="flex-grow flex items-center justify-center p-6 my-8">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 md:p-8 max-w-lg w-full glow-purple glass-panel">
            <div class="text-center mb-6">
                <div class="inline-block p-3 rounded-full bg-purple-500/10 text-purple-400 text-2xl mb-2">
                    <i class="fa-solid fa-user-plus animate-bounce"></i>
                </div>
                <h2 class="font-cyber text-2xl font-black tracking-wide text-white">PLAYER REGISTRATION</h2>
                <p class="text-xs text-gray-400 mt-1">Establish your system credentials to track logs, times, and status tiers.</p>
            </div>

            <?php if ($errors): ?>
            <div class="p-3 rounded-lg text-xs font-semibold mb-4 text-center bg-red-500/10 text-red-400 border border-red-500/30">
                <?= htmlspecialchars($errors) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1.5">Full Name</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-3.5 text-gray-500"><i class="fa-solid fa-signature"></i></span>
                            <input type="text" name="full_name" required placeholder="Muhammad Adham Bin Yahya"
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                   class="w-full bg-slate-950/80 border border-slate-800/80 rounded-lg p-3.5 pl-10 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1.5">Desired Username</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-3.5 text-gray-500"><i class="fa-solid fa-user-tag"></i></span>
                            <input type="text" name="username" required placeholder="e.g. Adham_Yahya"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   class="w-full bg-slate-950/80 border border-slate-800/80 rounded-lg p-3.5 pl-10 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1.5 font-cyber">Secure Passkey</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3.5 text-gray-500"><i class="fa-solid fa-key"></i></span>
                        <input type="password" name="password" required placeholder="Minimum 6 characters"
                               class="w-full bg-slate-950/80 border border-slate-800/80 rounded-lg p-3.5 pl-10 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                    </div>
                </div>
                <div class="flex items-start text-xs text-gray-400 space-x-2 pt-1">
                    <input type="checkbox" required class="accent-purple-600 rounded mt-0.5">
                    <span>I agree to uphold the TryHarder PC Hub code of conduct.</span>
                </div>
                <button type="submit" class="w-full font-cyber bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold py-3.5 rounded-lg text-xs tracking-widest transition-all glow-purple">
                    CREATE PLAYER PROFILE
                </button>
            </form>

            <div class="border-t border-slate-800 mt-6 pt-4 text-center">
                <p class="text-xs text-gray-400">Already have an operational account? <a href="index.php" class="text-purple-400 hover:underline font-bold">Log In Here</a></p>
            </div>
        </div>
    </main>

    <footer class="border-t border-purple-950/20 bg-slate-950/80 text-center py-8 px-6 text-xs text-gray-500">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-left space-y-1">
                <p>&copy; 2026 TryHarder PC Hub.</p>
            </div>
        </div>
    </footer>
</body>
</html>