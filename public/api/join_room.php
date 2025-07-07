<?php
// public/api/join_room.php

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
$name = $data['name'] ?? null;

if (empty($roomId) || empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID and name are required']);
    exit;
}

$pdo = getPDO();

try {
    // Проверяем, существует ли комната
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    if ($stmt->fetch() === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Room not found']);
        exit;
    }

    $participantId = v4();

    // Добавляем нового участника
    $stmt = $pdo->prepare("INSERT INTO participants (id, room_id, name, is_host) VALUES (?, ?, ?, ?)");
    $stmt->execute([$participantId, $roomId, $name, 0]);

    // Уведомляем всех в комнате, что состояние изменилось
    notifyRoom($roomId, 'participant_joined');

    // Отправляем ответ
    echo json_encode([
        'success' => true,
        'participantId' => $participantId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
