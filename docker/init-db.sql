-- База данных: `hestia_helper`
--
DROP DATABASE IF EXISTS `exampleDB`; 

CREATE DATABASE IF NOT EXISTS `hestia_helper` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `hestia_helper`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00";

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT(20) AUTO_INCREMENT PRIMARY KEY,
    `tid` BIGINT(20) NOT NULL UNIQUE,
    `user_name` VARCHAR(255) DEFAULT '',
    `first_name` VARCHAR(255) DEFAULT '',
    `last_name` VARCHAR(255) DEFAULT '',
    `created_at` TIMESTAMP DEFAULT  current_timestamp(),
    status INT(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS `messages` (
    `id` BIGINT(20) AUTO_INCREMENT PRIMARY KEY,
    `msg_id` bigint(20) NOT NULL UNIQUE,
    `user_id` bigint(20) NOT NULL,
    `chat_id` bigint(20) NOT NULL,
    `text` LONGTEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT  current_timestamp(),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS `notes` (
    `id` BIGINT(20) AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) NOT NULL,
    `title` VARCHAR(255) DEFAULT '',
    `content` LONGTEXT DEFAULT NULL,
    `tags` VARCHAR(255) DEFAULT '',
    `created_at` TIMESTAMP DEFAULT  current_timestamp(),
    FOREIGN KEY (user_id) REFERENCES users(id)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS `notices` (
    `id` BIGINT(20) AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) NOT NULL,
    `title` VARCHAR(255) DEFAULT '',
    `content` LONGTEXT DEFAULT NULL,
    `date_remind` TIMESTAMP NOT NULL DEFAULT  current_timestamp(),
    `status` VARCHAR(100) DEFAULT '',
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS user_statuses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(255) DEFAULT ''
);

--
-- Дамп данных таблицы `messages`
--

INSERT INTO `user_statuses` (`id`, `status`) VALUES
(0, 'main_menu'),
(1, 'search'),
(2, 'note'),
(3, 'notice'),
(4, 'setting'),
(11, 'search_sub'),
(12, 'search_result'),
(21, 'add_note'),
(22, 'edit_note'),
(23, 'delete_note'),
(31, 'add_notice'),
(32, 'edit_notice'),
(33, 'delte_notice'),
(34, 'add_notice_time')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);


COMMIT;

