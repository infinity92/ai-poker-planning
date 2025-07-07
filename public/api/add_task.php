<?php
// public/api/add_task.php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$roomId = $data['room_id'] ?? null;
$participantId = $data['participant_id'] ?? null;
$title = $data['title'] ?? null;

if (empty($roomId) || empty($participantId) || empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID, participant ID, and task title are required']);
    exit;
}

$pdo = getPDO();

try {
    // 1. Проверяем, является ли участник хостом комнаты
    $stmt = $pdo->prepare("SELECT r.created_by FROM rooms r JOIN participants p ON r.id = p.room_id WHERE p.id = ? AND r.id = ?");
    $stmt->execute([$participantId, $roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room || $room['created_by'] !== $participantId) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Only the host can add tasks.']);
        exit;
    }

    // 2. Добавляем задачу
    $stmt = $pdo->prepare("INSERT INTO tasks (room_id, title) VALUES (?, ?)");
    $stmt->execute([$roomId, $title]);

    // 3. Уведомляем всех в комнате
    notifyRoom($roomId, 'task_added');

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
