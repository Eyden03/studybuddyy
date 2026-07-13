<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/session_model.php';

$user = require_login();
$active = 'home';
$pageTitle = 'Study Sessions';
$pageSubtitle = 'Browse what is open on campus right now.';

$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'open', 'free', 'near', 'week'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$query = trim($_GET['q'] ?? '');
$sortOptions = [
    'soonest' => 'Soonest',
    'newest' => 'Newest',
    'popular' => 'Most joined',
    'cost' => 'Lowest cost',
];
$sort = $_GET['sort'] ?? 'soonest';
if (!isset($sortOptions[$sort])) {
    $sort = 'soonest';
}

$sessions = fetch_sessions($filter, $query, $user, $sort);

function filter_url(string $filter, string $query, string $sort): string
{
    $params = ['filter' => $filter, 'sort' => $sort];
    if ($query !== '') {
        $params['q'] = $query;
    }
    return 'home.php?' . http_build_query($params);
}

function filter_class(string $name, string $activeFilter): string
{
    if ($name === $activeFilter) {
        return 'pill text-white';
    }

    return 'pill bg-white border border-[var(--line)] text-[var(--ink)]';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home - StudyBuddy Finder</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--paper)]">
<div class="flex">
  <?php include 'includes/sidebar.php'; ?>

  <main class="flex-1 px-8 py-8 max-w-[1200px]">
    <?php include 'includes/topbar.php'; ?>

    <div class="flex flex-wrap items-center gap-2 mb-7">
      <a href="<?= e(filter_url('all', $query, $sort)) ?>" class="<?= filter_class('all', $filter) ?>" style="<?= $filter === 'all' ? 'background:var(--ink)' : '' ?>">All</a>
      <a href="<?= e(filter_url('open', $query, $sort)) ?>" class="<?= filter_class('open', $filter) ?>" style="<?= $filter === 'open' ? 'background:var(--ink)' : '' ?>">Open now</a>
      <a href="<?= e(filter_url('free', $query, $sort)) ?>" class="<?= filter_class('free', $filter) ?>" style="<?= $filter === 'free' ? 'background:var(--ink)' : '' ?>">Free</a>
      <a href="<?= e(filter_url('near', $query, $sort)) ?>" class="<?= filter_class('near', $filter) ?>" style="<?= $filter === 'near' ? 'background:var(--ink)' : '' ?>">Near me</a>
      <a href="<?= e(filter_url('week', $query, $sort)) ?>" class="<?= filter_class('week', $filter) ?>" style="<?= $filter === 'week' ? 'background:var(--ink)' : '' ?>">This week</a>
      <form action="home.php" method="GET" class="ml-auto flex items-center gap-2 text-sm text-[var(--muted)]">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <?php if ($query !== ''): ?>
          <input type="hidden" name="q" value="<?= e($query) ?>">
        <?php endif; ?>
        <label for="sort" class="whitespace-nowrap">Sort by</label>
        <div class="relative flex items-center">
          <select id="sort" name="sort" class="appearance-none bg-transparent pr-5 font-semibold text-[var(--ink)] outline-none cursor-pointer" onchange="this.form.submit()">
            <?php foreach ($sortOptions as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= selected_attr($sort, $value) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <svg class="pointer-events-none absolute right-0" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#83808F" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
        </div>
      </form>
    </div>

    <?php if ($query !== ''): ?>
      <p class="text-sm text-[var(--muted)] mb-5">Search results for <span class="font-semibold text-[var(--ink)]"><?= e($query) ?></span></p>
    <?php endif; ?>

    <?php if (!$sessions): ?>
      <div class="card p-10 text-center">
        <h2 class="font-display text-xl font-bold mb-2">No sessions found</h2>
        <p class="text-sm text-[var(--muted)] mb-6">Try a different search or host the session your classmates need.</p>
        <a href="create_session.php" class="btn-primary inline-block">Create Session</a>
      </div>
    <?php else: ?>
      <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($sessions as $session): ?>
          <?php
            $tab = session_tab($session['subject']);
            $full = is_session_full($session);
            $past = is_session_past($session);
            $statusLabel = $past ? 'Ended' : ($full ? 'Full' : 'Open');
            $statusStyle = $past
                ? 'background:#F0EEF5; color:var(--muted)'
                : ($full ? 'background:#FFEAE6; color:var(--coral)' : 'background:#EFFFD1; color:var(--lime-deep)');
          ?>
          <div class="session-card p-5">
            <div class="tab tab-<?= e($tab) ?>"></div>
            <div class="flex items-start justify-between mt-2">
              <p class="text-xs font-semibold" style="color:<?= e(tab_text_color($tab)) ?>"><?= e($session['subject']) ?></p>
              <span class="pill" style="<?= e($statusStyle) ?>"><?= e($statusLabel) ?></span>
            </div>
            <h3 class="font-display font-bold text-[17px] mt-1 leading-snug"><?= e($session['title']) ?></h3>
            <p class="text-xs text-[var(--muted)] mt-1">Hosted by <?= e($session['host_first_name'] . ' ' . $session['host_last_name']) ?> - <?= e($session['course_strand']) ?></p>

            <div class="mt-4 flex flex-col gap-1.5 text-xs text-[var(--muted)]">
              <span class="flex items-center gap-1.5">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#83808F" stroke-width="2"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= e($session['location']) ?>
              </span>
              <span class="flex items-center gap-1.5">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#83808F" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg>
                <?= e(format_session_date($session['session_date'])) ?> - <?= e(format_time_value($session['start_time'])) ?>
              </span>
            </div>

            <div class="flex items-center justify-between mt-5 pt-4 border-t border-[var(--line)]">
              <span class="font-mono-data text-xs text-[var(--muted)]"><?= e(spots_label($session)) ?> joined - <?= e(format_expenses($session['estimated_expenses'])) ?></span>
              <a href="session_details.php?id=<?= (int) $session['id'] ?>" class="btn-dark text-xs !py-2 !px-4">View Details</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
