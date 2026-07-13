<?php
// Expects $active to be set by the including page, e.g. $active = 'home';
if (!function_exists('e')) {
    require_once __DIR__ . '/helpers.php';
}

if (!isset($active)) { $active = ''; }
function navclass($name, $active) {
    return 'sb-link ' . ($name === $active ? 'active' : '');
}
?>
<aside class="sb-nav w-[240px] shrink-0 min-h-screen flex flex-col justify-between py-6 px-4">
  <div>
    <a href="home.php" class="flex items-center gap-2 px-2 mb-9">
      <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--lime)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#14151A" stroke-width="2.4" stroke-linecap="round"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
      </span>
      <span class="font-display text-white text-[17px] font-bold tracking-tight">StudyBuddy</span>
    </a>

    <nav class="flex flex-col gap-1">
      <a href="home.php" class="<?= navclass('home', $active) ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg>
        Home
      </a>
      <a href="my_sessions.php" class="<?= navclass('my_sessions', $active) ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg>
        My Sessions
      </a>
      <a href="create_session.php" class="<?= navclass('create_session', $active) ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
        Create Session
      </a>
      <a href="profile.php" class="<?= navclass('profile', $active) ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
        Profile
      </a>
      <?php if (function_exists('is_admin') && is_admin()): ?>
      <a href="admin/index.php" class="sb-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M12 3l8 4v5c0 5-3.4 8-8 9-4.6-1-8-4-8-9V7l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
        Admin Panel
      </a>
      <?php endif; ?>
    </nav>
  </div>

  <div class="flex flex-col gap-1">
    <div class="rounded-2xl p-4 mb-2" style="background:var(--ink-soft)">
      <p class="text-white text-sm font-semibold mb-1">Find your next study buddy</p>
      <p class="text-[#B7B5C2] text-xs leading-relaxed mb-3">Browse open sessions happening on campus this week.</p>
      <a href="home.php" class="btn-primary block text-center text-xs !py-2">Browse Sessions</a>
    </div>
    <a href="logout.php" class="sb-link">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
      Logout
    </a>
  </div>
</aside>
