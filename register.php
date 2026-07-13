<?php
require_once __DIR__ . '/includes/auth.php';

require_guest();

$errors = [];
$old = [
    'first_name' => trim($_POST['first_name'] ?? ''),
    'last_name' => trim($_POST['last_name'] ?? ''),
    'school' => trim($_POST['school'] ?? ''),
    'course_strand' => trim($_POST['course_strand'] ?? ''),
    'year_level' => trim($_POST['year_level'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    foreach (['first_name', 'last_name', 'school', 'course_strand', 'year_level', 'email'] as $field) {
        if ($old[$field] === '') {
            $errors[] = 'Please complete all required fields.';
            break;
        }
    }

    if (!$errors && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$errors && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors && empty($_POST['terms'])) {
        $errors[] = 'Please agree to the Terms of Service and Privacy Policy.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);

        if ($stmt->fetch()) {
            $errors[] = 'That email is already registered.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('
            INSERT INTO users (first_name, last_name, school, course_strand, year_level, email, password_hash, bio)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $old['first_name'],
            $old['last_name'],
            $old['school'],
            $old['course_strand'],
            $old['year_level'],
            $old['email'],
            password_hash($password, PASSWORD_DEFAULT),
            'New to StudyBuddy and ready to study with classmates.',
        ]);

        login_user((int) db()->lastInsertId());
        set_flash('success', 'Account created. Welcome to StudyBuddy!');
        redirect('home.php');
    }
}

$yearLevels = ['Grade 11', 'Grade 12', '1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - StudyBuddy Finder</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[var(--paper)] min-h-screen">

<header class="max-w-3xl mx-auto flex items-center justify-between px-6 py-8">
  <a href="index.php" class="flex items-center gap-2">
    <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:var(--ink)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--lime)" stroke-width="2.4" stroke-linecap="round"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
    </span>
    <span class="font-display text-lg font-bold">StudyBuddy</span>
  </a>
  <p class="text-sm text-[var(--muted)]">Already have an account? <a href="login.php" class="font-semibold text-[var(--ink)] hover:underline">Log in</a></p>
</header>

<main class="max-w-3xl mx-auto px-6 pb-20">
  <div class="card p-10">
    <span class="tab tab-violet" style="position:static; display:inline-block; margin-bottom:1rem;"></span>
    <h1 class="font-display text-3xl font-bold mb-2">Create your account</h1>
    <p class="text-sm text-[var(--muted)] mb-9">Tell us about your school so we can match you with the right sessions.</p>

    <?php if ($errors): ?>
      <div class="mb-7 rounded-xl border px-4 py-3 text-sm font-semibold" style="background:#FFEAE6; color:var(--coral); border-color:#FFDAD3">
        <?= e($errors[0]) ?>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="flex flex-col gap-8">

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">Personal details</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name']) ?>" placeholder="Juan" required>
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name']) ?>" placeholder="Dela Cruz" required>
          </div>
        </div>
      </div>

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">Academic info</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field">
            <label for="school">School</label>
            <input type="text" id="school" name="school" value="<?= e($old['school']) ?>" placeholder="University of the Philippines" required>
          </div>
          <div class="field">
            <label for="course_strand">Course / Strand</label>
            <input type="text" id="course_strand" name="course_strand" value="<?= e($old['course_strand']) ?>" placeholder="BS Computer Science" required>
          </div>
          <div class="field md:col-span-2">
            <label for="year_level">Year / Grade level</label>
            <select id="year_level" name="year_level" required>
              <option value="" disabled <?= selected_attr($old['year_level'], '') ?>>Select year level</option>
              <?php foreach ($yearLevels as $year): ?>
                <option <?= selected_attr($old['year_level'], $year) ?>><?= e($year) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--muted)] mb-4">Account</p>
        <div class="grid md:grid-cols-2 gap-5">
          <div class="field md:col-span-2">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" placeholder="you@school.edu.ph" required>
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
          </div>
          <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
          </div>
        </div>
      </div>

      <label class="flex items-start gap-2.5 text-sm text-[var(--muted)]">
        <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded accent-[var(--lime-deep)]" required>
        I agree to the Terms of Service and Privacy Policy.
      </label>

      <button type="submit" class="btn-primary w-full">Create account</button>
    </form>
  </div>
</main>

</body>
</html>
