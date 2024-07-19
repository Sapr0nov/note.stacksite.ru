<?php

namespace App\Classes\dbController;

use mysqli;

class Note
{
    private $MYSQLI;
    private $TABLE = 'notes';

    public function __construct(mysqli $mysqli) {
        $this->MYSQLI = $mysqli;
    }

    public function init() {
        $response = "";
        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE . " (
            id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint NOT NULL,
            title varchar(255) NULL DEFAULT '',
            content text NULL,
            created_at datetime NULL DEFAULT CURRENT_TIMESTAMP,
            tags varchar(255) NULL DEFAULT ''
        );";
        try {
            $this->MYSQLI->query($query);
            $response .= "Таблица " . $this->TABLE . " создана.\r\n";
        } catch (\Exception $e) {
            $response .= "Ошибка создания таблицы " . $this->TABLE . ": " . $e->getMessage() . "\r\n";
        }

        return $response;
    }

    public function add($user_id, $str) {
        $title = ''; 
        $content = $str; 
        $tags = json_encode([]);

        $query = "INSERT INTO " . $this->TABLE . " (user_id, title, content, tags) VALUES (?, ?, ?, ?)";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('isss', $user_id, $title, $content, $tags);

        try {
            $stmt->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function search($user_id, $str) {
        $search_str = '%' . $this->MYSQLI->real_escape_string($str) . '%';

        $query = "SELECT id as note_id, title, content, created_at as date, tags 
                  FROM " . $this->TABLE . " 
                  WHERE user_id = ? 
                  AND (title LIKE ? OR content LIKE ? OR tags LIKE ?)";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('isss', $user_id, $search_str, $search_str, $search_str);

        try {
            $stmt->execute();
            $result = $stmt->get_result();
            $notes = $result->fetch_all(MYSQLI_ASSOC);
            $result->close();
            return $notes;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function delete($user_id, $id) {
        $query = "DELETE FROM " . $this->TABLE . " WHERE user_id = ? AND id = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('ii', $user_id, $id);

        try {
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
