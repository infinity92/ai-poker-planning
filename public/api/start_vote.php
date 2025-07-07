<?php
// public/api/start_vote.php

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
$taskId = $data['task_id'] ?? null;

if (empty($roomId) || empty($participantId) || empty($taskId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID, Participant ID, and Task ID are required']);
    exit;
}

$pdo = getPDO();

try {
    // 1. Проверяем, является ли участник хостом
    $stmt = $pdo->prepare("SELECT created_by FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room || $room['created_by'] !== $participantId) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Only the host can start a vote.']);
        exit;
    }

    $pdo->beginTransaction();

    // 2. Сбрасываем статус всех других задач в этой комнате, которые могли быть на голосовании
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'pending' WHERE room_id = ? AND status = 'voting'");
    $stmt->execute([$roomId]);

    // 3. Очищаем старые голоса и результаты для задачи, за которую начинаем голосовать
    $stmt = $pdo->prepare("DELETE FROM votes WHERE task_id = ?");
    $stmt->execute([$taskId]);

    // 4. Устанавливаем статус 'voting' для выбранной задачи и сбрасываем ее счет
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'voting', final_score = NULL, median_score = NULL WHERE id = ? AND room_id = ?");
    $stmt->execute([$taskId, $roomId]);

    $pdo->commit();

    // 5. Уведомляем всех в комнате о начале голосования
    notifyRoom($roomId, 'vote_started', ['taskId' => $taskId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
