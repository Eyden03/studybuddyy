<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function create_notification(int $userId, string $title, string $body, ?int $sessionId = null, ?int $actorId = null): void
{
    if ($userId <= 0) {
        return;
    }

    $stmt = db()->prepare('
        INSERT INTO notifications (user_id, actor_id, session_id, title, body)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$userId, $actorId, $sessionId, $title, $body]);
}

function notify_session_participants(int $sessionId, int $excludeUserId, string $title, string $body, ?int $actorId = null): void
{
    $stmt = db()->prepare('
        SELECT user_id
        FROM session_participants
        WHERE session_id = ? AND user_id <> ?
    ');
    $stmt->execute([$sessionId, $excludeUserId]);

    foreach ($stmt->fetchAll() as $participant) {
        create_notification((int) $participant['user_id'], $title, $body, $sessionId, $actorId);
    }
}

function fetch_notifications(int $userId, int $limit = 8): array
{
    $limit = max(1, min(20, $limit));
    $stmt = db()->prepare("
        SELECT n.*, s.title AS session_title
        FROM notifications n
        LEFT JOIN study_sessions s ON s.id = n.session_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC, n.id DESC
        LIMIT {$limit}
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function unread_notifications_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function mark_notifications_read(int $userId): void
{
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
}

function notification_time_label(string $createdAt): string
{
    $created = new DateTimeImmutable($createdAt);
    $now = new DateTimeImmutable('now');
    $seconds = max(0, $now->getTimestamp() - $created->getTimestamp());

    if ($seconds < 60) {
        return 'Just now';
    }

    if ($seconds < 3600) {
        return floor($seconds / 60) . 'm ago';
    }

    if ($seconds < 86400) {
        return floor($seconds / 3600) . 'h ago';
    }

    if ($seconds < 172800) {
        return 'Yesterday';
    }

    return $created->format('M j');
}
