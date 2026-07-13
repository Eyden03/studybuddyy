<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $serverDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
        $server = new PDO($serverDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $database = str_replace('`', '``', DB_NAME);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        ensure_schema($pdo);

        return $pdo;
    } catch (Throwable $e) {
        $message = 'Database connection failed. Make sure MySQL is running in XAMPP and check includes/config.php.';

        // Keep technical details out of the browser, but retain the real cause in
        // Apache's PHP error log. CLI diagnostics can safely show it directly.
        error_log('[StudyBuddy database] ' . $e->getMessage());

        if (PHP_SAPI === 'cli') {
            throw new RuntimeException($message . ' Cause: ' . $e->getMessage(), 0, $e);
        }

        http_response_code(500);
        exit($message);
    }
}

function ensure_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            school VARCHAR(160) NOT NULL,
            course_strand VARCHAR(160) NOT NULL,
            year_level VARCHAR(40) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            bio TEXT NULL,
            avatar_path VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    ensure_column($pdo, 'users', 'role', "ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER avatar_path");
    ensure_column($pdo, 'users', 'status', "ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER role");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS study_sessions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            host_id INT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            subject VARCHAR(120) NOT NULL,
            course_strand VARCHAR(160) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(220) NOT NULL,
            session_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            capacity INT UNSIGNED NOT NULL,
            estimated_expenses DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_study_sessions_host
                FOREIGN KEY (host_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS session_participants (
            session_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (session_id, user_id),
            CONSTRAINT fk_session_participants_session
                FOREIGN KEY (session_id) REFERENCES study_sessions(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_session_participants_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            actor_id INT UNSIGNED NULL,
            session_id INT UNSIGNED NULL,
            title VARCHAR(160) NOT NULL,
            body VARCHAR(255) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_notifications_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_notifications_actor
                FOREIGN KEY (actor_id) REFERENCES users(id)
                ON DELETE SET NULL,
            CONSTRAINT fk_notifications_session
                FOREIGN KEY (session_id) REFERENCES study_sessions(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reports (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT UNSIGNED NOT NULL,
            reported_user_id INT UNSIGNED NULL,
            session_id INT UNSIGNED NULL,
            category VARCHAR(60) NOT NULL,
            details TEXT NOT NULL,
            status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
            admin_notes TEXT NULL,
            handled_by INT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_reports_reported_user FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT fk_reports_session FOREIGN KEY (session_id) REFERENCES study_sessions(id) ON DELETE SET NULL,
            CONSTRAINT fk_reports_admin FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_actions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            target_type VARCHAR(40) NOT NULL,
            target_id INT UNSIGNED NULL,
            details VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_admin_actions_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        seed_demo_data($pdo);
    }

    ensure_admin_account($pdo);

    $done = true;
}

function ensure_admin_account(PDO $pdo): void
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO users (first_name,last_name,school,course_strand,year_level,email,password_hash,bio,role,status) VALUES (?,?,?,?,?,?,?,?, 'admin','active')")
            ->execute(['StudyBuddy','Admin','StudyBuddy','Administration','Staff','admin@studybuddy.local',password_hash('admin123', PASSWORD_DEFAULT),'Platform administrator account.']);
    }
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare('
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ');
    $stmt->execute([$table, $column]);
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function seed_demo_data(PDO $pdo): void
{
    $pdo->beginTransaction();

    try {
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $users = [
            ['Jamie', 'Diaz', 'University of the Philippines', 'BS Computer Science', '3rd Year', 'jamie.diaz@school.edu.ph', 'CS junior focused on algorithms and backend dev. Usually cramming in the library between 5-8 PM.'],
            ['Marco', 'Reyes', 'University of the Philippines', 'BS Computer Science', '3rd Year', 'marco.reyes@school.edu.ph', 'Data structures enjoyer and whiteboard person.'],
            ['Angela', 'Ruiz', 'University of the Philippines', 'BS ECE', '2nd Year', 'angela.ruiz@school.edu.ph', 'Solves calculus problems with too many sticky notes.'],
            ['Kyla', 'Pascual', 'University of the Philippines', 'BS Chemistry', '4th Year', 'kyla.pascual@school.edu.ph', 'Organic chemistry tutor and reviewer.'],
            ['Diego', 'Santos', 'University of the Philippines', 'BS IT', '3rd Year', 'diego.santos@school.edu.ph', 'Usually online, usually revising thesis drafts.'],
            ['Nina', 'Cruz', 'University of the Philippines', 'BS Psychology', '2nd Year', 'nina.cruz@school.edu.ph', 'Statistics notes keeper.'],
            ['Paolo', 'Tan', 'University of the Philippines', 'BSBA', '1st Year', 'paolo.tan@school.edu.ph', 'Accounting review buddy.'],
        ];

        $insertUser = $pdo->prepare('
            INSERT INTO users (first_name, last_name, school, course_strand, year_level, email, password_hash, bio)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($users as $user) {
            $insertUser->execute([
                $user[0],
                $user[1],
                $user[2],
                $user[3],
                $user[4],
                $user[5],
                $password,
                $user[6],
            ]);
        }

        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $insertUser->execute(['StudyBuddy', 'Admin', 'StudyBuddy', 'Administration', 'Staff', 'admin@studybuddy.local', $adminHash, 'Platform administrator account.']);
        $pdo->exec("UPDATE users SET role = 'admin' WHERE email = 'admin@studybuddy.local'");

        $ids = [];
        $stmt = $pdo->query('SELECT id, email FROM users');
        foreach ($stmt->fetchAll() as $row) {
            $ids[$row['email']] = (int) $row['id'];
        }

        $today = new DateTimeImmutable('today');
        $sessions = [
            [
                $ids['jamie.diaz@school.edu.ph'],
                'Data Structures',
                'Linked Lists Deep Dive',
                'BS Computer Science',
                "Going through Chapter 5 - singly, doubly, and circular linked lists - before Monday's lab exam. Bring your laptop with the starter repo cloned.",
                'CS Building, Lab 3',
                $today->format('Y-m-d'),
                '17:30:00',
                '19:30:00',
                5,
                0,
                ['jamie.diaz@school.edu.ph', 'marco.reyes@school.edu.ph'],
            ],
            [
                $ids['angela.ruiz@school.edu.ph'],
                'Calculus II',
                'Finals Cram - Chapter 7',
                'BS ECE',
                'Reviewing integration techniques, sequences, and the problem types likely to show up in finals.',
                'Main Library, Room 204',
                $today->modify('+1 day')->format('Y-m-d'),
                '15:00:00',
                '17:00:00',
                6,
                0,
                ['angela.ruiz@school.edu.ph', 'marco.reyes@school.edu.ph', 'diego.santos@school.edu.ph', 'nina.cruz@school.edu.ph'],
            ],
            [
                $ids['kyla.pascual@school.edu.ph'],
                'Organic Chemistry',
                'Reaction Mechanisms',
                'BS Chemistry',
                'A fast but friendly mechanism drill session before the long exam.',
                'Science Hall 1B',
                $today->modify('+2 days')->format('Y-m-d'),
                '10:00:00',
                '12:00:00',
                6,
                50,
                ['kyla.pascual@school.edu.ph', 'jamie.diaz@school.edu.ph', 'marco.reyes@school.edu.ph', 'angela.ruiz@school.edu.ph', 'diego.santos@school.edu.ph', 'nina.cruz@school.edu.ph'],
            ],
            [
                $ids['jamie.diaz@school.edu.ph'],
                'Thesis Writing',
                'Methodology Peer Review',
                'BS IT',
                'Bring a draft of your methods section and one specific question you want feedback on.',
                'Online - Google Meet',
                $today->modify('+3 days')->format('Y-m-d'),
                '13:00:00',
                '15:00:00',
                8,
                0,
                ['jamie.diaz@school.edu.ph', 'diego.santos@school.edu.ph', 'nina.cruz@school.edu.ph'],
            ],
            [
                $ids['nina.cruz@school.edu.ph'],
                'Statistics',
                'Probability Review',
                'BS Psychology',
                'Practice set on probability distributions, expected value, and z-scores.',
                'Library Study Pod 2',
                $today->modify('+4 days')->format('Y-m-d'),
                '14:00:00',
                '16:00:00',
                5,
                0,
                ['nina.cruz@school.edu.ph', 'jamie.diaz@school.edu.ph', 'marco.reyes@school.edu.ph'],
            ],
            [
                $ids['paolo.tan@school.edu.ph'],
                'Financial Accounting',
                'Trial Balance Workshop',
                'BSBA',
                'Working through trial balance problems and common adjustment mistakes.',
                'Business Bldg, Room 110',
                $today->modify('+5 days')->format('Y-m-d'),
                '16:30:00',
                '18:00:00',
                4,
                30,
                ['paolo.tan@school.edu.ph'],
            ],
        ];

        $insertSession = $pdo->prepare('
            INSERT INTO study_sessions
                (host_id, subject, title, course_strand, description, location, session_date, start_time, end_time, capacity, estimated_expenses)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $insertParticipant = $pdo->prepare('
            INSERT INTO session_participants (session_id, user_id)
            VALUES (?, ?)
        ');

        foreach ($sessions as $session) {
            $insertSession->execute(array_slice($session, 0, 11));
            $sessionId = (int) $pdo->lastInsertId();

            foreach ($session[11] as $email) {
                $insertParticipant->execute([$sessionId, $ids[$email]]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
