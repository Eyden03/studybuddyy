<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/session_model.php';

$user = require_login();
$active = 'home';
$sessionId = (int) ($_GET['id'] ?? 0);
$session = $sessionId ? fetch_session($sessionId) : null;

if (!$session) {
    set_flash('error', 'Session not found.');
    redirect('home.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'join') {
        if (join_study_session($sessionId, (int) $user['id'])) {
            set_flash('success', 'You joined the session.');
        } else {
            set_flash('error', 'This session cannot be joined.');
        }
        redirect('session_details.php?id=' . $sessionId);
    }

    if ($action === 'leave') {
        if (leave_study_session($sessionId, (int) $user['id'])) {
            set_flash('success', 'You left the session.');
        } else {
            set_flash('error', 'Could not leave this session.');
        }
        redirect('session_details.php?id=' . $sessionId);
    }

    if ($action === 'add_user') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);

        if ((int) $session['host_id'] !== (int) $user['id']) {
            set_flash('error', 'Only the host can add classmates.');
        } elseif (add_user_to_session($sessionId, $targetUserId, (int) $user['id'])) {
            set_flash('success', 'Classmate added to the session.');
        } else {
            set_flash('error', 'That classmate could not be added.');
        }

        redirect('session_details.php?id=' . $sessionId);
    }
}

$session = fetch_session($sessionId);
$participants = fetch_participants($sessionId);
$joined = user_joined_session($sessionId, (int) $user['id']);
$isHost = (int) $session['host_id'] === (int) $user['id'];
$isFull = is_session_full($session);
$isPast = is_session_past($session);
$availableUsers = ($isHost && !$isFull && !$isPast) ? fetch_available_users_for_session($sessionId) : [];
$tab = session_tab($session['subject']);
$pageTitle = 'Session Details';
$pageSubtitle = 'Everything you need before you join.';
$progress = min(100, ((int) $session['participant_count'] / max(1, (int) $session['capacity'])) * 100);
$host = [
    'first_name' => $session['host_first_name'],
    'last_name' => $session['host_last_name'],
    'avatar_path' => $session['host_avatar_path'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($session['title']) ?> - StudyBuddy Finder</title>
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

    <a href="home.php" class="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--muted)] hover:text-[var(--ink)] mb-6">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 18l-6-6 6-6"/></svg>
      Back to sessions
    </a>

    <div class="grid lg:grid-cols-3 gap-6">

      <div class="lg:col-span-2 card p-8 relative overflow-hidden">
        <div class="tab tab-<?= e($tab) ?>"></div>
        <p class="text-xs font-semibold mt-2" style="color:<?= e(tab_text_color($tab)) ?>"><?= e($session['subject']) ?></p>
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="font-display text-[28px] font-bold mt-1"><?= e($session['title']) ?></h1>
            <p class="text-sm text-[var(--muted)] mt-1">Hosted by <span class="font-semibold text-[var(--ink)]"><?= e($session['host_first_name'] . ' ' . $session['host_last_name']) ?></span> - <?= e($session['course_strand']) ?></p>
          </div>
          <?php if ($isHost): ?>
            <a href="edit_session.php?id=<?= $sessionId ?>" class="btn-ghost bg-white text-sm">Edit</a>
          <?php endif; ?>
        </div>

        <div class="grid sm:grid-cols-3 gap-4 mt-7">
          <div class="rounded-2xl p-4" style="background:var(--paper)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg>
            <p class="text-xs text-[var(--muted)] mt-2">Date</p>
            <p class="text-sm font-semibold mt-0.5"><?= e(format_long_session_date($session['session_date'])) ?></p>
          </div>
          <div class="rounded-2xl p-4" style="background:var(--paper)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            <p class="text-xs text-[var(--muted)] mt-2">Time</p>
            <p class="text-sm font-semibold mt-0.5"><?= e(format_time_range($session['start_time'], $session['end_time'])) ?></p>
          </div>
          <div class="rounded-2xl p-4" style="background:var(--paper)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <p class="text-xs text-[var(--muted)] mt-2">Location</p>
            <p class="text-sm font-semibold mt-0.5"><?= e($session['location']) ?></p>
          </div>
        </div>

        <div class="mt-7">
          <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-3">Description</p>
          <p class="text-sm text-[var(--ink)] leading-relaxed"><?= nl2br(e($session['description'])) ?></p>
        </div>

        <div class="mt-7">
          <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-3">Participants (<?= e(spots_label($session)) ?>)</p>
          <div class="flex items-center gap-3 flex-wrap">
            <?php foreach ($participants as $participant): ?>
              <a href="#" title="<?= e(full_name($participant)) ?>">
                <?= avatar_html($participant, 'w-10 h-10 text-sm') ?>
              </a>
            <?php endforeach; ?>
            <?php if ($isHost && !$isFull && !$isPast): ?>
              <span class="w-10 h-10 rounded-full border-2 border-dashed border-[var(--line)] flex items-center justify-center text-[var(--muted)]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              </span>
            <?php endif; ?>
          </div>

          <?php if ($isHost): ?>
            <div class="mt-5 pt-5 border-t border-[var(--line)]">
              <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-3">Add classmate</p>
              <?php if ($isPast): ?>
                <p class="text-xs text-[var(--muted)]">This session has ended, so classmates cannot be added.</p>
              <?php elseif ($isFull): ?>
                <p class="text-xs text-[var(--muted)]">This session is full. Increase capacity before adding more classmates.</p>
              <?php elseif ($availableUsers): ?>
                <form action="session_details.php?id=<?= $sessionId ?>" method="POST" class="flex flex-col sm:flex-row sm:items-end gap-3">
                  <input type="hidden" name="action" value="add_user">
                  <div class="field flex-1">
                    <label for="user_id">User</label>
                    <select id="user_id" name="user_id" required>
                      <option value="" disabled selected>Choose a user</option>
                      <?php foreach ($availableUsers as $availableUser): ?>
                        <option value="<?= (int) $availableUser['id'] ?>"><?= e(full_name($availableUser)) ?> - <?= e($availableUser['course_strand']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <button type="submit" class="btn-dark text-sm">Add User</button>
                </form>
              <?php else: ?>
                <p class="text-xs text-[var(--muted)]">All available users are already in this session.</p>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card p-7 h-fit sticky top-8">
        <div class="progress-track h-2 mb-2">
          <div class="progress-fill h-2" style="width:<?= e((string) $progress) ?>%"></div>
        </div>
        <p class="text-xs font-mono-data text-[var(--muted)] mb-6"><?= e((string) $session['participant_count']) ?> of <?= e((string) $session['capacity']) ?> spots filled</p>

        <div class="flex items-center justify-between text-sm mb-3">
          <span class="text-[var(--muted)]">Estimated expenses</span>
          <span class="font-semibold"><?= e(format_expenses($session['estimated_expenses'])) ?></span>
        </div>
        <div class="flex items-center justify-between text-sm mb-6 pb-6 border-b border-[var(--line)]">
          <span class="text-[var(--muted)]">Course focus</span>
          <span class="font-semibold"><?= e($session['course_strand']) ?></span>
        </div>

        <?php if ($isHost): ?>
          <a href="edit_session.php?id=<?= $sessionId ?>" class="btn-dark block text-center w-full">Edit Session</a>
          <p class="text-xs text-center text-[var(--muted)] mt-3">You are hosting this session.</p>
        <?php elseif ($isPast): ?>
          <button class="btn-ghost bg-white w-full cursor-not-allowed" disabled>Session Ended</button>
        <?php elseif ($joined): ?>
          <div class="pill w-full justify-center mb-3" style="background:#EFFFD1; color:var(--lime-deep)">You have already joined this session</div>
          <form action="session_details.php?id=<?= $sessionId ?>" method="POST">
            <input type="hidden" name="action" value="leave">
            <button type="submit" class="btn-ghost bg-white w-full" style="color:var(--coral); border-color:#FFDAD3">Leave Session</button>
          </form>
        <?php elseif ($isFull): ?>
          <button class="btn-ghost bg-white w-full cursor-not-allowed" style="color:var(--coral); border-color:#FFDAD3" disabled>Session Full</button>
        <?php else: ?>
          <form action="session_details.php?id=<?= $sessionId ?>" method="POST">
            <input type="hidden" name="action" value="join">
            <button class="btn-primary w-full">Join Session</button>
          </form>
          <p class="text-xs text-center text-[var(--muted)] mt-3">You'll get a confirmation once you join.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php if (!$isHost): ?>
      <div class="mt-5 text-center"><a href="report.php?session_id=<?= $sessionId ?>" class="text-xs font-semibold" style="color:var(--coral)">Report this session or host</a></div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
