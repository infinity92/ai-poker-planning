<?php
// public/helpers.php

function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $dbHost = 'mysql';
        $dbName = 'poker';
        $dbUser = 'pokeruser';
        $dbPass = 'pokerpass';

        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function getRedis() {
    static $redis = null;
    if ($redis === null) {
        $redis = new Redis();
        try {
            $redis->connect('redis', 6379);
        } catch (RedisException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Redis connection failed: ' . $e->getMessage()]));
        }
    }
    return $redis;
}

function notifyRoom($roomId, $type, $data = []) {
    $redis = getRedis();
    $channel = 'room:' . $roomId;
    $payload = json_encode([
        'type' => $type,
        'data' => $data
    ]);
    $redis->publish($channel, $payload);
}
