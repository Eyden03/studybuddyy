<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/session_model.php';

$user = require_login();
$active = 'create_session';
$pageTitle = 'Create a Session';
$pageSubtitle = 'Fill in the details and invite classmates to join.';
$errors = [];

$old = [
    'title' => trim($_POST['title'] ?? ''),
    'subject' => trim($_POST['subject'] ?? ''),
    'course_strand' => trim($_POST['course_strand'] ?? $user['course_strand']),
    'description' => trim($_POST['description'] ?? ''),
    'location' => trim($_POST['location'] ?? ''),
    'session_date' => trim($_POST['session_date'] ?? ''),
    'start_time' => trim($_POST['start_time'] ?? ''),
    'end_time' => trim($_POST['end_time'] ?? ''),
    'capacity' => trim($_POST['capacity'] ?? ''),
    'estimated_expenses' => trim($_POST['estimated_expenses'] ?? '0'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('
                INSERT INTO study_sessions
                    (host_id, title, subject, course_strand, description, location, session_date, start_time, end_time, capacity, estimated_expenses)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                (int) $user['id'],
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
            ]);

            $sessionId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO session_participants (session_id, user_id) VALUES (?, ?)');
            $stmt->execute([$sessionId, (int) $user['id']]);

            $pdo->commit();
            set_flash('success', 'Session created.');
            redirect('session_details.php?id=' . $sessionId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Session - StudyBuddy Finder</title>
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

    <form action="create_session.php" method="POST" class="card p-8 flex flex-col gap-7">

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">Session basics</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field md:col-span-2">
            <label for="title">Session title</label>
            <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" placeholder="e.g. Linked Lists Deep Dive" required>
          </div>
          <div class="field">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" placeholder="e.g. Data Structures" required>
          </div>
          <div class="field">
            <label for="course_strand">Course / Strand</label>
            <input type="text" id="course_strand" name="course_strand" value="<?= e($old['course_strand']) ?>" placeholder="e.g. BS Computer Science" required>
          </div>
          <div class="field md:col-span-2">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="What will you cover? What should participants bring?" required><?= e($old['description']) ?></textarea>
          </div>
        </div>
      </div>

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">When & where</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field md:col-span-2">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= e($old['location']) ?>" placeholder="e.g. Main Library, Room 204 or Online - Google Meet" required>
          </div>
          <div class="field">
            <label for="session_date">Date</label>
            <input type="date" id="session_date" name="session_date" value="<?= e($old['session_date']) ?>" min="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>" required>
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
            <input type="number" id="capacity" name="capacity" min="2" value="<?= e($old['capacity']) ?>" placeholder="e.g. 5" required>
          </div>
          <div class="field">
            <label for="estimated_expenses">Estimated expenses <span class="text-[var(--muted)] font-normal">(optional)</span></label>
            <input type="number" id="estimated_expenses" name="estimated_expenses" min="0" step="0.01" value="<?= e($old['estimated_expenses']) ?>" placeholder="0 for free">
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn-primary">Create Session</button>
        <a href="home.php" class="btn-ghost bg-white">Cancel</a>
      </div>
    </form>
  </main>
</div>
</body>
</html>
