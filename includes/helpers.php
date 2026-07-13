<?php
require_once __DIR__ . '/config.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page, and try again.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $flashes;
}

function render_flashes(): void
{
    $flashes = get_flashes();

    if (!$flashes) {
        return;
    }

    foreach ($flashes as $flash) {
        $isError = ($flash['type'] ?? '') === 'error';
        $style = $isError
            ? 'background:#FFEAE6; color:var(--coral); border-color:#FFDAD3'
            : 'background:#EFFFD1; color:var(--lime-deep); border-color:#DDF6A1';
        echo '<div class="mb-5 rounded-xl border px-4 py-3 text-sm font-semibold" style="' . $style . '">' . e($flash['message']) . '</div>';
    }
}

function full_name(array $user): string
{
    return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}

function initials(array $user): string
{
    $first = trim($user['first_name'] ?? '');
    $last = trim($user['last_name'] ?? '');
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

function avatar_html(array $user, string $classes = 'w-10 h-10 text-sm'): string
{
    $path = $user['avatar_path'] ?? '';
    $absolutePath = $path ? dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path) : '';

    if ($path && is_file($absolutePath)) {
        return '<img src="' . e($path) . '" alt="' . e(full_name($user)) . '" class="' . e($classes) . ' rounded-full object-cover border border-[var(--line)]">';
    }

    return '<span class="avatar ' . e($classes) . '" style="background:var(--violet); color:#fff;">' . e(initials($user)) . '</span>';
}

function selected_attr($actual, $expected): string
{
    return (string) $actual === (string) $expected ? 'selected' : '';
}

function checked_attr(bool $checked): string
{
    return $checked ? 'checked' : '';
}

function format_session_date(string $date): string
{
    $sessionDate = new DateTimeImmutable($date);
    $today = new DateTimeImmutable('today');
    $tomorrow = $today->modify('+1 day');

    if ($sessionDate->format('Y-m-d') === $today->format('Y-m-d')) {
        return 'Today';
    }

    if ($sessionDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
        return 'Tomorrow';
    }

    return $sessionDate->format('D, M j');
}

function format_long_session_date(string $date): string
{
    $sessionDate = new DateTimeImmutable($date);
    $today = new DateTimeImmutable('today');
    $tomorrow = $today->modify('+1 day');

    if ($sessionDate->format('Y-m-d') === $today->format('Y-m-d')) {
        return 'Today, ' . $sessionDate->format('M j');
    }

    if ($sessionDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
        return 'Tomorrow, ' . $sessionDate->format('M j');
    }

    return $sessionDate->format('D, M j');
}

function format_time_value(string $time): string
{
    return (new DateTimeImmutable($time))->format('g:i A');
}

function format_time_range(string $start, string $end): string
{
    return format_time_value($start) . ' - ' . format_time_value($end);
}

function format_expenses($amount): string
{
    $amount = (float) $amount;
    if ($amount <= 0) {
        return 'Free';
    }

    return 'PHP ' . rtrim(rtrim(number_format($amount, 2), '0'), '.');
}

function session_tab(string $subject): string
{
    $colors = ['lime', 'violet', 'coral', 'sky'];
    return $colors[abs(crc32(strtolower($subject))) % count($colors)];
}

function tab_text_color(string $tab): string
{
    if ($tab === 'lime') {
        return 'var(--lime-deep)';
    }

    return 'var(--' . $tab . ')';
}

function is_session_past(array $session): bool
{
    $endsAt = new DateTimeImmutable($session['session_date'] . ' ' . $session['end_time']);
    return $endsAt < new DateTimeImmutable('now');
}

function is_session_full(array $session): bool
{
    return (int) $session['participant_count'] >= (int) $session['capacity'];
}

function spots_label(array $session): string
{
    return (int) $session['participant_count'] . '/' . (int) $session['capacity'];
}
