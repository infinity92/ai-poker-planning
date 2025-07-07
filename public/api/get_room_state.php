<?php
// public/api/get_room_state.php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$roomId = $_GET['room_id'] ?? null;

if (empty($roomId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID is required']);
    exit;
}

$pdo = getPDO();

try {
    // 1. Получаем информацию о комнате
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo json_encode(['error' => 'Room not found']);
        exit;
    }

    // 2. Получаем участников
    $stmt = $pdo->prepare("SELECT id, name, is_host FROM participants WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Получаем задачи и их голоса
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE room_id = ? ORDER BY created_at ASC");
    $stmt->execute([$roomId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $taskIds = array_map(function($t) { return $t['id']; }, $tasks);
    $votes = [];

    if (!empty($taskIds)) {
        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $stmt = $pdo->prepare("SELECT task_id, participant_id, score FROM votes WHERE task_id IN ($placeholders)");
        $stmt->execute($taskIds);
        $allVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Группируем голоса по задачам
        foreach ($allVotes as $vote) {
            if (!isset($votes[$vote['task_id']])) {
                $votes[$vote['task_id']] = [];
            }
            $votes[$vote['task_id']][] = $vote;
        }
    }

    // Добавляем голоса к задачам
    foreach ($tasks as &$task) {
        $task['votes'] = $votes[$task['id']] ?? [];
    }

    // Собираем итоговое состояние
    $state = [
        'room' => $room,
        'participants' => $participants,
        'tasks' => $tasks
    ];

    echo json_encode($state);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

