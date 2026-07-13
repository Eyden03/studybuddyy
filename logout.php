<?php
require_once __DIR__ . '/includes/auth.php';

logout_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logged out - StudyBuddy Finder</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--paper)] min-h-screen flex items-center justify-center">
  <div class="card p-10 max-w-sm w-full text-center">
    <span class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-5" style="background:var(--lime)">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#14151A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
    </span>
    <h1 class="font-display text-xl font-bold mb-2">You've been logged out</h1>
    <p class="text-sm text-[var(--muted)] mb-7">See you at the next study session.</p>
    <a href="login.php" class="btn-primary block">Log back in</a>
  </div>
</body>
</html>
