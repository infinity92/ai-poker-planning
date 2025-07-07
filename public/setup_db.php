<?php
// public/setup_db.php

// Включаем отображение всех ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

// --- Параметры подключения к БД ---
$dbHost = 'mysql';
$dbName = 'poker';
$dbUser = 'pokeruser';
$dbPass = 'pokerpass';
$dbRootPass = 'rootpassword'; // Пароль root для создания БД

try {
    // Сначала подключаемся к MySQL серверу без выбора конкретной БД
    $pdo = new PDO("mysql:host=$dbHost", 'root', $dbRootPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Создаем БД, если она не существует
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$dbName`;");

    echo "[SUCCESS] Database '$dbName' is ready.\n";

} catch (PDOException $e) {
    die("[ERROR] DB Connection Failed: " . $e->getMessage());
}

// --- SQL для создания таблиц ---
$sql = "
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_by` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `participants` (
  `id` varchar(36) NOT NULL,
  `room_id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_host` tinyint(1) NOT NULL DEFAULT '0',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `participants_room_id_foreign` (`room_id`),
  CONSTRAINT `participants_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('pending','voting','completed') NOT NULL DEFAULT 'pending',
  `final_score` decimal(5,2) DEFAULT NULL,
  `median_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tasks_room_id_foreign` (`room_id`),
  CONSTRAINT `tasks_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `participant_id` varchar(36) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_participant_unique` (`task_id`,`participant_id`),
  KEY `votes_task_id_foreign` (`task_id`),
  KEY `votes_participant_id_foreign` (`participant_id`),
  CONSTRAINT `votes_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `votes_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    // Выполняем весь SQL-блок
    $pdo->exec($sql);
    echo "[SUCCESS] All tables created successfully!\n";
} catch (PDOException $e) {
    die("[ERROR] Table creation failed: " . $e->getMessage());
}

