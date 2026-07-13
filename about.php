<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About — StudyBuddy Finder</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--paper)]">

<header class="max-w-7xl mx-auto flex items-center justify-between px-6 py-6">
  <a href="index.php" class="flex items-center gap-2">
    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--ink)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--lime)" stroke-width="2.4" stroke-linecap="round"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
    </span>
    <span class="font-display text-lg font-bold">StudyBuddy</span>
  </a>
  <div class="flex items-center gap-3">
    <a href="login.php" class="btn-ghost text-sm !py-2 !px-4">Log in</a>
    <a href="register.php" class="btn-primary text-sm !py-2 !px-4">Register</a>
  </div>
</header>

<main class="max-w-3xl mx-auto px-6 py-14">
  <span class="pill mb-5" style="background:#fff; border:1px solid var(--line)">About the project</span>
  <h1 class="font-display text-4xl font-bold mb-5">Studying is better with company.</h1>
  <p class="text-[var(--muted)] leading-relaxed mb-6">
    StudyBuddy Finder started as a simple idea: the hardest part of studying isn't the material,
    it's finding people who are tackling the same material at the same time. So we built a place
    where any student can post a session — a subject, a location, a time — and let classmates
    fill the open seats.
  </p>
  <p class="text-[var(--muted)] leading-relaxed mb-10">
    Whether it's a last-minute review before finals or a weekly thesis check-in, StudyBuddy Finder
    keeps it organized: one page for hosting, one for joining, and one for tracking everything you're
    part of.
  </p>

  <div class="grid sm:grid-cols-3 gap-4">
    <div class="card p-5">
      <div class="tab tab-lime" style="position:static; display:inline-block;"></div>
      <p class="font-display font-bold text-lg mt-3">Host</p>
      <p class="text-sm text-[var(--muted)] mt-1">Set the topic, time, and headcount.</p>
    </div>
    <div class="card p-5">
      <div class="tab tab-violet" style="position:static; display:inline-block;"></div>
      <p class="font-display font-bold text-lg mt-3">Join</p>
      <p class="text-sm text-[var(--muted)] mt-1">Browse by subject and reserve a slot.</p>
    </div>
    <div class="card p-5">
      <div class="tab tab-coral" style="position:static; display:inline-block;"></div>
      <p class="font-display font-bold text-lg mt-3">Track</p>
      <p class="text-sm text-[var(--muted)] mt-1">Everything lives in My Sessions.</p>
    </div>
  </div>
</main>

<footer class="max-w-7xl mx-auto px-6 py-10 border-t border-[var(--line)]">
  <p class="text-sm text-[var(--muted)]">© 2026 StudyBuddy Finder. Built for students, by students.</p>
</footer>

</body>
</html>
