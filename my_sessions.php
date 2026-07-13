<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/session_model.php';

$user = require_login();
$active = 'my_sessions';
$pageTitle = 'My Sessions';
$pageSubtitle = 'Sessions you are hosting and sessions you have joined.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'leave') {
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    if (leave_study_session($sessionId, (int) $user['id'])) {
        set_flash('success', 'You left the session.');
    } else {
        set_flash('error', 'Could not leave that session.');
    }
    redirect('my_sessions.php');
}

$hostedSessions = fetch_hosted_sessions((int) $user['id']);
$joinedSessions = fetch_joined_sessions((int) $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Sessions - StudyBuddy Finder</title>
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

    <section class="mb-10">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-display text-lg font-bold flex items-center gap-2">
          <span class="w-2 h-2 rounded-full" style="background:var(--lime-deep)"></span>
          Sessions I'm Hosting
        </h2>
        <a href="create_session.php" class="text-sm font-semibold text-[var(--ink)] hover:underline">+ New session</a>
      </div>

      <?php if (!$hostedSessions): ?>
        <div class="card p-7">
          <p class="text-sm text-[var(--muted)] mb-4">You are not hosting anything yet.</p>
          <a href="create_session.php" class="btn-primary inline-block text-sm">Create Session</a>
        </div>
      <?php else: ?>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
          <?php foreach ($hostedSessions as $session): ?>
            <?php $tab = session_tab($session['subject']); ?>
            <div class="session-card p-5">
              <div class="tab tab-<?= e($tab) ?>"></div>
              <p class="text-xs font-semibold mt-2" style="color:<?= e(tab_text_color($tab)) ?>"><?= e($session['subject']) ?></p>
              <h3 class="font-display font-bold text-[17px] mt-1"><?= e($session['title']) ?></h3>
              <p class="text-xs text-[var(--muted)] mt-2"><?= e(format_session_date($session['session_date'])) ?> - <?= e(format_time_value($session['start_time'])) ?></p>
              <div class="flex items-center justify-between mt-4">
                <span class="font-mono-data text-xs text-[var(--muted)]"><?= e(spots_label($session)) ?> participants</span>
              </div>
              <div class="flex items-center gap-2 mt-4 pt-4 border-t border-[var(--line)]">
                <a href="edit_session.php?id=<?= (int) $session['id'] ?>" class="btn-ghost bg-white text-xs !py-2 !px-3 flex-1 text-center">Edit</a>
                <a href="session_details.php?id=<?= (int) $session['id'] ?>" class="btn-dark text-xs !py-2 !px-3 flex-1 text-center">View</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section>
      <h2 class="font-display text-lg font-bold flex items-center gap-2 mb-4">
        <span class="w-2 h-2 rounded-full" style="background:var(--violet)"></span>
        Sessions I've Joined
      </h2>

      <?php if (!$joinedSessions): ?>
        <div class="card p-7">
          <p class="text-sm text-[var(--muted)] mb-4">You have not joined any sessions yet.</p>
          <a href="home.php" class="btn-dark inline-block text-sm">Browse Sessions</a>
        </div>
      <?php else: ?>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
          <?php foreach ($joinedSessions as $session): ?>
            <?php $tab = session_tab($session['subject']); ?>
            <div class="session-card p-5">
              <div class="tab tab-<?= e($tab) ?>"></div>
              <p class="text-xs font-semibold mt-2" style="color:<?= e(tab_text_color($tab)) ?>"><?= e($session['subject']) ?></p>
              <h3 class="font-display font-bold text-[17px] mt-1"><?= e($session['title']) ?></h3>
              <p class="text-xs text-[var(--muted)] mt-2">Hosted by <?= e($session['host_first_name'] . ' ' . $session['host_last_name']) ?> - <?= e(format_session_date($session['session_date'])) ?></p>
              <div class="flex items-center gap-2 mt-4 pt-4 border-t border-[var(--line)]">
                <a href="session_details.php?id=<?= (int) $session['id'] ?>" class="btn-dark text-xs !py-2 !px-3 flex-1 text-center">View Details</a>
                <form action="my_sessions.php" method="POST">
                  <input type="hidden" name="action" value="leave">
                  <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                  <button type="submit" class="btn-ghost bg-white text-xs !py-2 !px-3" style="color:var(--coral); border-color:#FFDAD3" onclick="return confirm('Leave this session?')">Leave</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>
</body>
</html>
