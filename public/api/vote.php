<?php
// public/api/vote.php

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
$taskId = $data['task_id'] ?? null;
$participantId = $data['participant_id'] ?? null;
$score = $data['score'] ?? null;

if (empty($roomId) || empty($taskId) || empty($participantId) || !isset($score)) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID, Task ID, Participant ID, and score are required']);
    exit;
}

$pdo = getPDO();

try {
    // Используем INSERT ... ON DUPLICATE KEY UPDATE, чтобы участник мог изменить свой голос
    $stmt = $pdo->prepare(
        "INSERT INTO votes (task_id, participant_id, score) VALUES (?, ?, ?) ".
        "ON DUPLICATE KEY UPDATE score = VALUES(score)"
    );
    $stmt->execute([$taskId, $participantId, $score]);

    // Уведомляем всех в комнате, что кто-то проголосовал
    notifyRoom($roomId, 'user_voted', ['participantId' => $participantId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

