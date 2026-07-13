<?php
require_once __DIR__ . '/includes/auth.php';

require_guest();

$errors = [];
$email = trim($_POST['email'] ?? '');
$next = $_GET['next'] ?? 'home.php';
$nextParts = parse_url($next);
if (!$next || isset($nextParts['host']) || strpos($next, '//') === 0) {
    $next = 'home.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter your email and password.';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user((int) $user['id']);
            set_flash('success', 'Welcome back, ' . $user['first_name'] . '!');
            redirect(is_admin($user) ? 'admin/index.php' : $next);
        }

        $errors[] = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in - StudyBuddy Finder</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--paper)] min-h-screen grid lg:grid-cols-2">

  <div class="flex flex-col justify-center px-8 md:px-20 py-14">
    <a href="index.php" class="flex items-center gap-2 mb-12">
      <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--ink)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--lime)" stroke-width="2.4" stroke-linecap="round"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
      </span>
      <span class="font-display text-lg font-bold">StudyBuddy</span>
    </a>

    <div class="max-w-sm w-full">
      <h1 class="font-display text-3xl font-bold mb-2">Welcome back</h1>
      <p class="text-sm text-[var(--muted)] mb-8">Log in to find your next study session.</p>

      <?php render_flashes(); ?>

      <?php if ($errors): ?>
        <div class="mb-5 rounded-xl border px-4 py-3 text-sm font-semibold" style="background:#FFEAE6; color:var(--coral); border-color:#FFDAD3">
          <?= e($errors[0]) ?>
        </div>
      <?php endif; ?>

      <form action="login.php?next=<?= urlencode($next) ?>" method="POST" class="flex flex-col gap-5">
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="you@school.edu.ph" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Password" required>
        </div>
        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 text-[var(--muted)]">
            <input type="checkbox" class="rounded accent-[var(--lime-deep)]"> Remember me
          </label>
          <span class="font-semibold text-[var(--muted)]">Demo: password123</span>
        </div>
        <button type="submit" class="btn-primary w-full mt-2">Log in</button>
      </form>

      <p class="text-sm text-[var(--muted)] mt-8">
        Don't have an account? <a href="register.php" class="font-semibold text-[var(--ink)] hover:underline">Register</a>
      </p>
      <p class="text-xs text-[var(--muted)] mt-3">Admin demo: admin@studybuddy.local / admin123</p>
    </div>
  </div>

  <div class="hidden lg:flex relative overflow-hidden items-center justify-center notebook-lines" style="background:var(--ink)">
    <div class="absolute inset-0 opacity-[0.06]" style="background-image: repeating-linear-gradient(to bottom, transparent, transparent 27px, #fff 27px, #fff 28px);"></div>
    <div class="relative z-10 max-w-sm px-10">
      <div class="session-card p-6 mb-6 -rotate-2">
        <div class="tab tab-lime"></div>
        <p class="text-xs font-semibold mt-2" style="color:var(--lime-deep)">Statistics</p>
        <h3 class="font-display font-bold text-lg mt-1">Probability Review</h3>
        <div class="flex items-center justify-between mt-4">
          <span class="pill" style="background:#EFFFD1; color:var(--lime-deep)">Open - 3/5</span>
        </div>
      </div>
      <h2 class="font-display text-2xl font-bold text-white leading-snug">"Found my thesis group here in one afternoon."</h2>
      <p class="text-sm mt-3" style="color:#B7B5C2">Every session starts with someone hitting Join.</p>
    </div>
  </div>

</body>
</html>
