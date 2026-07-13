<?php
require_once __DIR__ . '/includes/auth.php';

$user = require_login();
$active = 'profile';
$pageTitle = 'Edit Profile';
$pageSubtitle = 'Keep your info up to date.';
$errors = [];
$yearLevels = ['Grade 11', 'Grade 12', '1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];

$old = [
    'first_name' => trim($_POST['first_name'] ?? $user['first_name']),
    'last_name' => trim($_POST['last_name'] ?? $user['last_name']),
    'school' => trim($_POST['school'] ?? $user['school']),
    'course_strand' => trim($_POST['course_strand'] ?? $user['course_strand']),
    'year_level' => trim($_POST['year_level'] ?? $user['year_level']),
    'bio' => trim($_POST['bio'] ?? ($user['bio'] ?? '')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['first_name', 'last_name', 'school', 'course_strand', 'year_level'] as $field) {
        if ($old[$field] === '') {
            $errors[] = 'Please complete all required fields.';
            break;
        }
    }

    $avatarPath = null;
    if (!$errors && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The profile photo could not be uploaded.';
        } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Profile photo must be 2MB or smaller.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];

            if (!isset($extensions[$mime])) {
                $errors[] = 'Profile photo must be a PNG or JPG image.';
            } else {
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0775, true);
                }

                $fileName = 'user_' . (int) $user['id'] . '_' . time() . '.' . $extensions[$mime];
                $target = UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;

                if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                    $errors[] = 'The profile photo could not be saved.';
                } else {
                    $avatarPath = UPLOAD_URL . '/' . $fileName;
                }
            }
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('
            UPDATE users
            SET first_name = ?, last_name = ?, school = ?, course_strand = ?, year_level = ?, bio = ?, avatar_path = COALESCE(?, avatar_path)
            WHERE id = ?
        ');
        $stmt->execute([
            $old['first_name'],
            $old['last_name'],
            $old['school'],
            $old['course_strand'],
            $old['year_level'],
            $old['bio'],
            $avatarPath,
            (int) $user['id'],
        ]);

        set_flash('success', 'Profile updated.');
        redirect('profile.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile - StudyBuddy Finder</title>
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

    <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="card p-8 flex flex-col gap-7">

      <div class="flex items-center gap-5">
        <?= avatar_html($user, 'w-20 h-20 text-2xl') ?>
        <div>
          <label for="avatar" class="btn-ghost bg-white text-sm inline-block cursor-pointer">Change photo</label>
          <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg" class="hidden">
          <p class="text-xs text-[var(--muted)] mt-2">PNG or JPG, up to 2MB.</p>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-5">
        <div class="field">
          <label for="first_name">First name</label>
          <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name']) ?>" required>
        </div>
        <div class="field">
          <label for="last_name">Last name</label>
          <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name']) ?>" required>
        </div>
        <div class="field">
          <label for="school">School</label>
          <input type="text" id="school" name="school" value="<?= e($old['school']) ?>" required>
        </div>
        <div class="field">
          <label for="course_strand">Course / Strand</label>
          <input type="text" id="course_strand" name="course_strand" value="<?= e($old['course_strand']) ?>" required>
        </div>
        <div class="field">
          <label for="year_level">Year / Grade level</label>
          <select id="year_level" name="year_level" required>
            <?php foreach ($yearLevels as $year): ?>
              <option <?= selected_attr($old['year_level'], $year) ?>><?= e($year) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field md:col-span-2">
          <label for="bio">Bio</label>
          <textarea id="bio" name="bio"><?= e($old['bio']) ?></textarea>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn-primary">Save Changes</button>
        <a href="profile.php" class="btn-ghost bg-white">Cancel</a>
      </div>
    </form>
  </main>
</div>
</body>
</html>
