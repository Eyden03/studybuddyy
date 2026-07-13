<?php
if (!function_exists('current_user')) {
    require_once __DIR__ . '/auth.php';
}

if (!isset($pageTitle)) { $pageTitle = 'Dashboard'; }
if (!isset($pageSubtitle)) { $pageSubtitle = ''; }

$topbarUser = current_user();
$searchQuery = $_GET['q'] ?? '';
$topbarNotifications = [];
$unreadNotifications = 0;

if ($topbarUser) {
    require_once __DIR__ . '/notification_model.php';
    $topbarNotifications = fetch_notifications((int) $topbarUser['id']);
    $unreadNotifications = unread_notifications_count((int) $topbarUser['id']);
}
?>
<header class="flex items-center justify-between mb-8 gap-6">
  <div>
    <h1 class="font-display text-[26px] font-bold text-[var(--ink)]"><?= e($pageTitle) ?></h1>
    <?php if ($pageSubtitle): ?>
      <p class="text-sm text-[var(--muted)] mt-0.5"><?= e($pageSubtitle) ?></p>
    <?php endif; ?>
  </div>

  <div class="flex items-center gap-4">
    <form action="home.php" method="GET" class="hidden md:flex items-center gap-2 bg-white border border-[var(--line)] rounded-full pl-4 pr-2 py-2 w-[280px]">
      <?php if (isset($filter) && $filter !== 'all'): ?>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
      <?php endif; ?>
      <?php if (isset($sort) && $sort !== 'soonest'): ?>
        <input type="hidden" name="sort" value="<?= e($sort) ?>">
      <?php endif; ?>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#83808F" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="Search sessions, subjects..." class="bg-transparent text-sm outline-none w-full placeholder:text-[var(--muted)]">
    </form>
    <?php if ($topbarUser): ?>
    <div class="relative" id="notification-menu">
      <button type="button" id="notification-button" class="w-10 h-10 rounded-full bg-white border border-[var(--line)] flex items-center justify-center relative" aria-label="Notifications" aria-expanded="false">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#14151A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        <?php if ($unreadNotifications > 0): ?>
          <span id="notification-dot" class="absolute top-2 right-2.5 w-1.5 h-1.5 rounded-full" style="background:var(--coral)"></span>
        <?php endif; ?>
      </button>
      <div id="notification-panel" class="hidden absolute right-0 mt-3 w-[330px] card overflow-hidden z-30">
        <div class="flex items-center justify-between px-4 py-3">
          <h2 class="font-display text-base font-bold">Notifications</h2>
          <?php if ($unreadNotifications > 0): ?>
            <span id="notification-count" class="pill" style="background:#FFEAE6; color:var(--coral)"><?= e((string) $unreadNotifications) ?> new</span>
          <?php endif; ?>
        </div>

        <?php if (!$topbarNotifications): ?>
          <div class="border-t border-[var(--line)] px-4 py-6 text-center">
            <p class="text-sm font-semibold text-[var(--ink)]">No notifications yet</p>
            <p class="text-xs text-[var(--muted)] mt-1">Session updates will show here.</p>
          </div>
        <?php else: ?>
          <div class="max-h-[360px] overflow-y-auto scrollbar-thin">
            <?php foreach ($topbarNotifications as $notification): ?>
              <?php
                $isUnread = !(bool) $notification['is_read'];
                $notificationHref = $notification['session_id'] ? 'session_details.php?id=' . (int) $notification['session_id'] : '';
                $notificationClass = 'block border-t border-[var(--line)] px-4 py-3 hover:bg-[var(--paper)] transition';
              ?>
              <?php if ($notificationHref): ?>
                <a href="<?= e($notificationHref) ?>" class="<?= e($notificationClass) ?>" data-unread="<?= $isUnread ? '1' : '0' ?>">
              <?php else: ?>
                <div class="<?= e($notificationClass) ?>" data-unread="<?= $isUnread ? '1' : '0' ?>">
              <?php endif; ?>
                  <div class="flex items-start gap-3">
                    <span class="mt-1 w-2 h-2 rounded-full shrink-0" style="background:<?= $isUnread ? 'var(--coral)' : 'var(--line)' ?>"></span>
                    <span>
                      <span class="block text-sm <?= $isUnread ? 'font-semibold' : 'font-medium' ?> text-[var(--ink)]"><?= e($notification['title']) ?></span>
                      <span class="block text-xs text-[var(--muted)] mt-0.5 leading-relaxed"><?= e($notification['body']) ?></span>
                      <span class="block text-[11px] text-[var(--muted)] mt-1"><?= e(notification_time_label($notification['created_at'])) ?></span>
                    </span>
                  </div>
              <?php if ($notificationHref): ?>
                </a>
              <?php else: ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($topbarUser): ?>
    <a href="profile.php" class="flex items-center gap-2.5 pl-1">
      <?= avatar_html($topbarUser, 'w-10 h-10 text-sm') ?>
      <span class="hidden lg:block">
        <span class="block text-sm font-semibold leading-tight"><?= e(full_name($topbarUser)) ?></span>
        <span class="block text-xs text-[var(--muted)] leading-tight"><?= e($topbarUser['course_strand']) ?></span>
      </span>
    </a>
    <?php else: ?>
      <a href="login.php" class="btn-dark text-sm !py-2 !px-4">Log in</a>
    <?php endif; ?>
  </div>
</header>
<?php render_flashes(); ?>
<?php if ($topbarUser): ?>
<script>
(() => {
  const button = document.getElementById('notification-button');
  const panel = document.getElementById('notification-panel');
  const dot = document.getElementById('notification-dot');
  const count = document.getElementById('notification-count');
  let markedRead = false;

  if (!button || !panel) {
    return;
  }

  const closePanel = () => {
    panel.classList.add('hidden');
    button.setAttribute('aria-expanded', 'false');
  };

  const markRead = () => {
    if (markedRead) {
      return;
    }

    markedRead = true;
    fetch('notifications.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(() => {
      dot?.remove();
      count?.remove();
      panel.querySelectorAll('[data-unread="1"]').forEach((item) => {
        item.setAttribute('data-unread', '0');
      });
    }).catch(() => {});
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = !panel.classList.contains('hidden');

    if (isOpen) {
      closePanel();
      return;
    }

    panel.classList.remove('hidden');
    button.setAttribute('aria-expanded', 'true');
    markRead();
  });

  panel.addEventListener('click', (event) => event.stopPropagation());
  document.addEventListener('click', closePanel);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closePanel();
    }
  });
})();
</script>
<?php endif; ?>
