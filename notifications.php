<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notification_model.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mark_notifications_read((int) $user['id']);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

redirect('home.php');
