<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudyBuddy Finder — Find people to study with</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--paper)]">

<!-- NAV -->
<header class="max-w-7xl mx-auto flex items-center justify-between px-6 py-6">
  <a href="index.php" class="flex items-center gap-2">
    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--ink)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--lime)" stroke-width="2.4" stroke-linecap="round"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
    </span>
    <span class="font-display text-lg font-bold">StudyBuddy</span>
  </a>
  <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-[var(--muted)]">
    <a href="#how" class="hover:text-[var(--ink)]">How it works</a>
    <a href="#subjects" class="hover:text-[var(--ink)]">Subjects</a>
    <a href="about.php" class="hover:text-[var(--ink)]">About</a>
  </nav>
  <div class="flex items-center gap-3">
    <a href="login.php" class="btn-ghost text-sm !py-2 !px-4">Log in</a>
    <a href="register.php" class="btn-primary text-sm !py-2 !px-4">Register</a>
  </div>
</header>

<!-- HERO -->
<section class="max-w-7xl mx-auto px-6 pt-10 pb-20 grid lg:grid-cols-2 gap-14 items-center notebook-lines rounded-[28px]">
  <div>
    <span class="pill mb-6" style="background:#fff; border:1px solid var(--line); color:var(--ink)">
      <span class="w-1.5 h-1.5 rounded-full" style="background:var(--lime-deep)"></span>
      Now open across 40+ schools
    </span>
    <h1 class="font-display text-[44px] md:text-[58px] leading-[1.05] font-bold text-[var(--ink)]">
      Stop studying<br> alone. <span class="relative inline-block">
        Find your
        <svg class="absolute -bottom-1 left-0 w-full" height="10" viewBox="0 0 200 10" preserveAspectRatio="none"><path d="M0 6 Q50 0 100 6 T200 6" stroke="var(--lime)" stroke-width="8" fill="none"/></svg>
      </span> study crew.
    </h1>
    <p class="mt-6 text-[17px] text-[var(--muted)] max-w-md leading-relaxed">
      Post a session, pick a subject, and match with classmates who are cramming for the same exam — same campus, same course, same deadline.
    </p>
    <div class="mt-8 flex items-center gap-4">
      <a href="register.php" class="btn-primary">Find a study buddy</a>
      <a href="home.php" class="btn-ghost bg-white">Browse sessions</a>
    </div>
    <div class="mt-10 flex items-center gap-6">
      <div class="flex -space-x-3">
        <span class="avatar w-9 h-9 text-xs border-2 border-[var(--paper)]" style="background:var(--lime)">MJ</span>
        <span class="avatar w-9 h-9 text-xs border-2 border-[var(--paper)]" style="background:var(--violet); color:#fff">AR</span>
        <span class="avatar w-9 h-9 text-xs border-2 border-[var(--paper)]" style="background:var(--coral); color:#fff">KP</span>
        <span class="avatar w-9 h-9 text-xs border-2 border-[var(--paper)]" style="background:var(--ink); color:#fff">+2k</span>
      </div>
      <p class="text-sm text-[var(--muted)]">joined a session this month</p>
    </div>
  </div>

  <!-- Hero visual: mock session card stack -->
  <div class="relative h-[440px]">
    <div class="session-card absolute top-0 right-4 w-[300px] p-5 rotate-3">
      <div class="tab tab-violet"></div>
      <p class="text-xs font-semibold text-[var(--violet)] mt-2">Calculus II</p>
      <h3 class="font-display font-bold text-lg mt-1">Finals Cram — Chapter 7</h3>
      <p class="text-xs text-[var(--muted)] mt-2">Library, Room 204 · Fri 3:00 PM</p>
      <div class="flex items-center justify-between mt-4">
        <span class="pill" style="background:#F1EEFF; color:var(--violet)">4/6 joined</span>
        <span class="font-mono-data text-xs text-[var(--muted)]">₱0 · Free</span>
      </div>
    </div>
    <div class="session-card absolute top-[150px] left-0 w-[300px] p-5 -rotate-2 z-10">
      <div class="tab tab-lime"></div>
      <p class="text-xs font-semibold" style="color:var(--lime-deep)">Data Structures</p>
      <h3 class="font-display font-bold text-lg mt-1">Linked Lists Deep Dive</h3>
      <p class="text-xs text-[var(--muted)] mt-2">CS Building, Lab 3 · Today 5:30 PM</p>
      <div class="flex items-center justify-between mt-4">
        <span class="pill" style="background:#EFFFD1; color:var(--lime-deep)">Open · 2/5</span>
        <span class="font-mono-data text-xs text-[var(--muted)]">₱50 · snacks</span>
      </div>
    </div>
    <div class="session-card absolute bottom-0 right-0 w-[280px] p-5 rotate-1">
      <div class="tab tab-coral"></div>
      <p class="text-xs font-semibold" style="color:var(--coral)">Organic Chemistry</p>
      <h3 class="font-display font-bold text-lg mt-1">Reaction Mechanisms</h3>
      <p class="text-xs text-[var(--muted)] mt-2">Science Hall · Sat 10:00 AM</p>
      <span class="pill mt-4" style="background:#FFEAE6; color:var(--coral)">Session Full</span>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how" class="max-w-7xl mx-auto px-6 py-16">
  <p class="text-xs font-semibold tracking-widest uppercase text-[var(--muted)] mb-2">The process</p>
  <h2 class="font-display text-3xl font-bold mb-12">Three steps to your next study session</h2>
  <div class="grid md:grid-cols-3 gap-6">
    <div class="card p-7">
      <span class="w-10 h-10 rounded-xl flex items-center justify-center font-display font-bold mb-5" style="background:var(--lime)">1</span>
      <h3 class="font-display font-bold text-lg mb-2">Post or browse</h3>
      <p class="text-sm text-[var(--muted)] leading-relaxed">Host your own session or browse what's open by subject, course, and campus location.</p>
    </div>
    <div class="card p-7">
      <span class="w-10 h-10 rounded-xl flex items-center justify-center font-display font-bold mb-5 text-white" style="background:var(--violet)">2</span>
      <h3 class="font-display font-bold text-lg mb-2">Match by course</h3>
      <p class="text-sm text-[var(--muted)] leading-relaxed">See who's hosting, their course and strand, and how many slots are still open.</p>
    </div>
    <div class="card p-7">
      <span class="w-10 h-10 rounded-xl flex items-center justify-center font-display font-bold mb-5 text-white" style="background:var(--ink)">3</span>
      <h3 class="font-display font-bold text-lg mb-2">Join and show up</h3>
      <p class="text-sm text-[var(--muted)] leading-relaxed">Reserve your spot in one tap, track it in My Sessions, and get studying.</p>
    </div>
  </div>
</section>

<!-- SUBJECTS -->
<section id="subjects" class="max-w-7xl mx-auto px-6 py-16">
  <div class="rounded-[28px] p-10 md:p-14" style="background:var(--ink)">
    <p class="text-xs font-semibold tracking-widest uppercase mb-2" style="color:var(--lime)">Popular right now</p>
    <h2 class="font-display text-3xl font-bold text-white mb-8">Sessions open across every subject</h2>
    <div class="flex flex-wrap gap-3">
      <?php
        $subjects = ['Calculus II','Data Structures','Organic Chemistry','Statistics','Thesis Writing','Physics 101','Financial Accounting','UI/UX Design','Microbiology','Philippine History'];
        foreach ($subjects as $s) {
          echo '<span class="pill text-white text-sm" style="background:var(--ink-soft)">'.$s.'</span>';
        }
      ?>
    </div>
    <a href="register.php" class="btn-primary inline-block mt-10">Create your free account</a>
  </div>
</section>

<footer class="max-w-7xl mx-auto px-6 py-10 flex items-center justify-between border-t border-[var(--line)]">
  <p class="text-sm text-[var(--muted)]">© 2026 StudyBuddy Finder. Built for students, by students.</p>
  <div class="flex gap-6 text-sm text-[var(--muted)]">
    <a href="about.php" class="hover:text-[var(--ink)]">About</a>
    <a href="login.php" class="hover:text-[var(--ink)]">Log in</a>
  </div>
</footer>

</body>
</html>
