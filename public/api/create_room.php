<?php
// public/api/create_room.php

header('Content-Type: application/json');

// Функция для генерации UUID v4
function v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$hostName = $data['name'] ?? null;

if (empty($hostName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Host name is required']);
    exit;
}

// Параметры для подключения к БД (в идеале, их нужно выносить в переменные окружения)
$dbHost = 'mysql';
$dbName = 'poker';
$dbUser = 'pokeruser';
$dbPass = 'pokerpass';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    $roomId = v4();
    $hostId = v4();

    // Создаем комнату
    $stmt = $pdo->prepare("INSERT INTO rooms (id, name, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$roomId, "Planning Room", $hostId]);

    // Добавляем хоста как участника
    $stmt = $pdo->prepare("INSERT INTO participants (id, room_id, name, is_host) VALUES (?, ?, ?, ?)");
    $stmt->execute([$hostId, $roomId, $hostName, 1]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'roomId' => $roomId,
        'participantId' => $hostId,
        'url' => "/?room=" . $roomId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

