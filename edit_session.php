<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/session_model.php';

$user = require_login();
$active = 'my_sessions';
$pageTitle = 'Edit Session';
$pageSubtitle = 'Update the details for your study session.';
$errors = [];

$sessionId = (int) ($_GET['id'] ?? 0);
$session = $sessionId ? fetch_session($sessionId) : null;

if (!$session || (int) $session['host_id'] !== (int) $user['id']) {
    set_flash('error', 'Session not found or you do not have permission to edit it.');
    redirect('my_sessions.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    delete_study_session($sessionId, (int) $user['id']);
    set_flash('success', 'Session deleted.');
    redirect('my_sessions.php');
}

$old = [
    'title' => trim($_POST['title'] ?? $session['title']),
    'subject' => trim($_POST['subject'] ?? $session['subject']),
    'course_strand' => trim($_POST['course_strand'] ?? $session['course_strand']),
    'description' => trim($_POST['description'] ?? $session['description']),
    'location' => trim($_POST['location'] ?? $session['location']),
    'session_date' => trim($_POST['session_date'] ?? $session['session_date']),
    'start_time' => trim($_POST['start_time'] ?? substr($session['start_time'], 0, 5)),
    'end_time' => trim($_POST['end_time'] ?? substr($session['end_time'], 0, 5)),
    'capacity' => trim($_POST['capacity'] ?? $session['capacity']),
    'estimated_expenses' => trim($_POST['estimated_expenses'] ?? $session['estimated_expenses']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'save') === 'save') {
    foreach (['title', 'subject', 'course_strand', 'description', 'location', 'session_date', 'start_time', 'end_time', 'capacity'] as $field) {
        if ($old[$field] === '') {
            $errors[] = 'Please complete all required fields.';
            break;
        }
    }

    $capacity = (int) $old['capacity'];
    $expenses = $old['estimated_expenses'] === '' ? 0 : (float) $old['estimated_expenses'];

    if (!$errors && $capacity < 2) {
        $errors[] = 'Maximum participants must be at least 2.';
    }

    if (!$errors && $capacity < (int) $session['participant_count']) {
        $errors[] = 'Capacity cannot be lower than the number of joined participants.';
    }

    if (!$errors && $expenses < 0) {
        $errors[] = 'Estimated expenses cannot be negative.';
    }

    if (!$errors) {
        $startAt = new DateTimeImmutable($old['session_date'] . ' ' . $old['start_time']);
        $endAt = new DateTimeImmutable($old['session_date'] . ' ' . $old['end_time']);

        if ($endAt <= $startAt) {
            $errors[] = 'End time must be later than start time.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('
            UPDATE study_sessions
            SET title = ?, subject = ?, course_strand = ?, description = ?, location = ?, session_date = ?, start_time = ?, end_time = ?, capacity = ?, estimated_expenses = ?
            WHERE id = ? AND host_id = ?
        ');
        $stmt->execute([
            $old['title'],
            $old['subject'],
            $old['course_strand'],
            $old['description'],
            $old['location'],
            $old['session_date'],
            $old['start_time'],
            $old['end_time'],
            $capacity,
            $expenses,
            $sessionId,
            (int) $user['id'],
        ]);

        notify_session_participants(
            $sessionId,
            (int) $user['id'],
            'Session updated',
            '"' . $old['title'] . '" has new details. Please check the latest schedule.',
            (int) $user['id']
        );

        set_flash('success', 'Session updated.');
        redirect('session_details.php?id=' . $sessionId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Session - StudyBuddy Finder</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--paper)]">
<div class="flex">
  <?php include 'includes/sidebar.php'; ?>

  <main class="flex-1 px-8 py-8 max-w-[900px]">
    <?php include 'includes/topbar.php'; ?>

    <?php if ($errors): ?>
      <div class="mb-5 rounded-xl border px-4 py-3 text-sm font-semibold" style="background:#FFEAE6; color:var(--coral); border-color:#FFDAD3">
        <?= e($errors[0]) ?>
      </div>
    <?php endif; ?>

    <form action="edit_session.php?id=<?= $sessionId ?>" method="POST" class="card p-8 flex flex-col gap-7">

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">Session basics</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field md:col-span-2">
            <label for="title">Session title</label>
            <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" required>
          </div>
          <div class="field">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" required>
          </div>
          <div class="field">
            <label for="course_strand">Course / Strand</label>
            <input type="text" id="course_strand" name="course_strand" value="<?= e($old['course_strand']) ?>" required>
          </div>
          <div class="field md:col-span-2">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= e($old['description']) ?></textarea>
          </div>
        </div>
      </div>

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">When & where</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field md:col-span-2">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= e($old['location']) ?>" required>
          </div>
          <div class="field">
            <label for="session_date">Date</label>
            <input type="date" id="session_date" name="session_date" value="<?= e($old['session_date']) ?>" required>
          </div>
          <div class="field"></div>
          <div class="field">
            <label for="start_time">Start time</label>
            <input type="time" id="start_time" name="start_time" value="<?= e($old['start_time']) ?>" required>
          </div>
          <div class="field">
            <label for="end_time">End time</label>
            <input type="time" id="end_time" name="end_time" value="<?= e($old['end_time']) ?>" required>
          </div>
        </div>
      </div>

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">Capacity & cost</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field">
            <label for="capacity">Maximum participants</label>
            <input type="number" id="capacity" name="capacity" min="<?= (int) $session['participant_count'] ?>" value="<?= e($old['capacity']) ?>" required>
          </div>
          <div class="field">
            <label for="estimated_expenses">Estimated expenses <span class="text-[var(--muted)] font-normal">(optional)</span></label>
            <input type="number" id="estimated_expenses" name="estimated_expenses" min="0" step="0.01" value="<?= e($old['estimated_expenses']) ?>">
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" name="action" value="save" class="btn-primary">Save Changes</button>
        <a href="my_sessions.php" class="btn-ghost bg-white">Cancel</a>
        <button type="submit" name="action" value="delete" class="ml-auto text-sm font-semibold" style="color:var(--coral)" onclick="return confirm('Delete this session?')">Delete session</button>
      </div>
    </form>
  </main>
</div>
</body>
</html>
