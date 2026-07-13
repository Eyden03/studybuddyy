<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/notification_model.php';

function session_select_sql(): string
{
    return "
        SELECT
            s.*,
            u.first_name AS host_first_name,
            u.last_name AS host_last_name,
            u.school AS host_school,
            u.course_strand AS host_course,
            u.email AS host_email,
            u.avatar_path AS host_avatar_path,
            (
                SELECT COUNT(*)
                FROM session_participants sp
                WHERE sp.session_id = s.id
            ) AS participant_count
        FROM study_sessions s
        INNER JOIN users u ON u.id = s.host_id
    ";
}

function session_order_sql(string $sort): string
{
    if ($sort === 'newest') {
        return 's.created_at DESC, s.session_date ASC, s.start_time ASC';
    }

    if ($sort === 'popular') {
        return 'participant_count DESC, s.session_date ASC, s.start_time ASC';
    }

    if ($sort === 'cost') {
        return 's.estimated_expenses ASC, s.session_date ASC, s.start_time ASC';
    }

    return 's.session_date ASC, s.start_time ASC';
}

function fetch_sessions(string $filter = 'all', string $query = '', ?array $user = null, string $sort = 'soonest'): array
{
    $where = [];
    $params = [];

    if ($query !== '') {
        $where[] = "(s.title LIKE ? OR s.subject LIKE ? OR s.description LIKE ? OR s.location LIKE ? OR s.course_strand LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $term = '%' . $query . '%';
        array_push($params, $term, $term, $term, $term, $term, $term, $term);
    }

    if ($filter === 'open') {
        $where[] = "(SELECT COUNT(*) FROM session_participants sp WHERE sp.session_id = s.id) < s.capacity";
        $where[] = "TIMESTAMP(s.session_date, s.end_time) >= NOW()";
    } elseif ($filter === 'free') {
        $where[] = "s.estimated_expenses = 0";
    } elseif ($filter === 'week') {
        $where[] = "s.session_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($filter === 'near' && $user) {
        $where[] = "u.school = ?";
        $params[] = $user['school'];
    }

    $sql = session_select_sql();
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ' . session_order_sql($sort);

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_user_summary(int $userId): ?array
{
    $stmt = db()->prepare('SELECT id, first_name, last_name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function fetch_session(int $id): ?array
{
    $stmt = db()->prepare(session_select_sql() . ' WHERE s.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $session = $stmt->fetch();
    return $session ?: null;
}

function fetch_hosted_sessions(int $userId): array
{
    $stmt = db()->prepare(session_select_sql() . ' WHERE s.host_id = ? ORDER BY s.session_date ASC, s.start_time ASC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function fetch_joined_sessions(int $userId): array
{
    $stmt = db()->prepare(
        session_select_sql() . '
        INNER JOIN session_participants me ON me.session_id = s.id AND me.user_id = ?
        WHERE s.host_id <> ?
        ORDER BY s.session_date ASC, s.start_time ASC'
    );
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll();
}

function fetch_participants(int $sessionId): array
{
    $stmt = db()->prepare('
        SELECT u.*
        FROM session_participants sp
        INNER JOIN users u ON u.id = sp.user_id
        WHERE sp.session_id = ?
        ORDER BY sp.joined_at ASC
    ');
    $stmt->execute([$sessionId]);
    return $stmt->fetchAll();
}

function fetch_available_users_for_session(int $sessionId): array
{
    $stmt = db()->prepare('
        SELECT u.*
        FROM users u
        WHERE NOT EXISTS (
            SELECT 1
            FROM session_participants sp
            WHERE sp.session_id = ? AND sp.user_id = u.id
        )
        ORDER BY u.first_name ASC, u.last_name ASC
    ');
    $stmt->execute([$sessionId]);
    return $stmt->fetchAll();
}

function user_joined_session(int $sessionId, int $userId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM session_participants WHERE session_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$sessionId, $userId]);
    return (bool) $stmt->fetchColumn();
}

function join_study_session(int $sessionId, int $userId): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $session = fetch_session($sessionId);
        if (!$session || is_session_past($session) || is_session_full($session)) {
            $pdo->rollBack();
            return false;
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO session_participants (session_id, user_id) VALUES (?, ?)');
        $stmt->execute([$sessionId, $userId]);
        $joined = $stmt->rowCount() > 0;

        if ($joined && (int) $session['host_id'] !== $userId) {
            $participant = fetch_user_summary($userId);
            $participantName = $participant ? full_name($participant) : 'A classmate';
            create_notification(
                (int) $session['host_id'],
                $participantName . ' joined your session',
                $participantName . ' joined "' . $session['title'] . '".',
                $sessionId,
                $userId
            );
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function leave_study_session(int $sessionId, int $userId): bool
{
    $session = fetch_session($sessionId);

    if (!$session || (int) $session['host_id'] === $userId) {
        return false;
    }

    $stmt = db()->prepare('DELETE FROM session_participants WHERE session_id = ? AND user_id = ?');
    $stmt->execute([$sessionId, $userId]);
    $left = $stmt->rowCount() > 0;

    if ($left) {
        $participant = fetch_user_summary($userId);
        $participantName = $participant ? full_name($participant) : 'A classmate';
        create_notification(
            (int) $session['host_id'],
            $participantName . ' left your session',
            $participantName . ' left "' . $session['title'] . '".',
            $sessionId,
            $userId
        );
    }

    return $left;
}

function add_user_to_session(int $sessionId, int $targetUserId, int $hostId): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $session = fetch_session($sessionId);
        $targetUser = fetch_user_summary($targetUserId);

        if (
            !$session ||
            !$targetUser ||
            (int) $session['host_id'] !== $hostId ||
            is_session_past($session) ||
            is_session_full($session)
        ) {
            $pdo->rollBack();
            return false;
        }

        $stmt = $pdo->prepare('INSERT IGNORE INTO session_participants (session_id, user_id) VALUES (?, ?)');
        $stmt->execute([$sessionId, $targetUserId]);
        $added = $stmt->rowCount() > 0;

        if ($added && $targetUserId !== $hostId) {
            $host = [
                'first_name' => $session['host_first_name'],
                'last_name' => $session['host_last_name'],
            ];
            create_notification(
                $targetUserId,
                'You were added to a session',
                full_name($host) . ' added you to "' . $session['title'] . '".',
                $sessionId,
                $hostId
            );
        }

        $pdo->commit();
        return $added;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function delete_study_session(int $sessionId, int $hostId): bool
{
    $session = fetch_session($sessionId);

    if (!$session || (int) $session['host_id'] !== $hostId) {
        return false;
    }

    notify_session_participants(
        $sessionId,
        $hostId,
        'Session canceled',
        '"' . $session['title'] . '" was canceled by the host.',
        $hostId
    );

    $stmt = db()->prepare('DELETE FROM study_sessions WHERE id = ? AND host_id = ?');
    $stmt->execute([$sessionId, $hostId]);
    return $stmt->rowCount() > 0;
}
