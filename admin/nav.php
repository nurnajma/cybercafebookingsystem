<?php
// Auto-detect current page from filename — no variable needed
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
// Map filenames to nav keys
$pageMap = [
    'dashboard' => 'dashboard',
    'stations'  => 'stations',
    'bookings'  => 'bookings',
    'users'     => 'users',
];
$activePage = $pageMap[$currentPage] ?? '';

function navItem(string $href, string $icon, string $label, string $key, string $active): string {
    $cls = ($active === $key) ? 'nav-item active' : 'nav-item';
    return "<a href=\"$href\" class=\"$cls\"><i class=\"fa-solid fa-$icon\"></i><span>$label</span></a>";
}
?>
<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
            <div class="logo-title">TRYHARDER</div>
            <div class="logo-sub">ADMIN CORE</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?= navItem('dashboard.php', 'gauge-high',     'Dashboard', 'dashboard', $activePage) ?>
        <?= navItem('stations.php',  'desktop',        'Stations',  'stations',  $activePage) ?>
        <?= navItem('bookings.php',  'calendar-check', 'Bookings',  'bookings',  $activePage) ?>
        <?= navItem('users.php',     'users',          'Users',     'users',     $activePage) ?>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar"><?= htmlspecialchars(strtoupper(substr($adminUser['username'], 0, 2))) ?></div>
            <div>
                <div class="admin-name"><?= htmlspecialchars($adminUser['username']) ?></div>
                <div class="admin-role">System Administrator</div>
            </div>
        </div>
        <form method="POST" action="../logout.php">
            <button type="submit" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </button>
        </form>
    </div>
</aside>