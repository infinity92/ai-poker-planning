<?php
// public/api/reveal_cards.php

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
        echo json_encode(['error' => 'Forbidden: Only the host can reveal cards.']);
        exit;
    }

    // 2. Получаем все голоса за задачу
    $stmt = $pdo->prepare("SELECT score FROM votes WHERE task_id = ?");
    $stmt->execute([$taskId]);
    $votes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // 3. Считаем среднее и медиану, исключая нечисловые значения
    $numericVotes = array_filter($votes, 'is_numeric');
    $average = 0;
    $median = 0;

    if (count($numericVotes) > 0) {
        $average = array_sum($numericVotes) / count($numericVotes);
        sort($numericVotes);
        $mid = floor((count($numericVotes) - 1) / 2);
        if (count($numericVotes) % 2) { // нечетное число
            $median = $numericVotes[$mid];
        } else { // четное
            $median = ($numericVotes[$mid] + $numericVotes[$mid + 1]) / 2.0;
        }
    }

    // 4. Обновляем задачу: статус и результаты
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', final_score = ?, median_score = ? WHERE id = ?");
    $stmt->execute([round($average, 2), round($median, 2), $taskId]);

    // 5. Уведомляем всех в комнате
    notifyRoom($roomId, 'cards_revealed', ['taskId' => $taskId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

