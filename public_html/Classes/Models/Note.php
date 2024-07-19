<?php
namespace Models;

class Note {
    private $conn;

    public function __construct() {
        $this->conn = new \mysqli(getenv('DB_SERVER'), getenv('DB_USER'), getenv('DB_PASSWORD'), getenv('DB_NAME'));
        // Установка кодировки соединения
        if (!$this->conn->set_charset("utf8mb4")) {
            die("Error loading character set utf8mb4: " . $this->conn->error);
        }
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function add($title, $content, $tags) {
        $sql = "INSERT INTO notes (title, content, tags) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sss', $title, $content, $tags);
        if ($stmt->execute() === TRUE) {
            return "New note added successfully";
        } else {
            return "Error: " . $sql . "<br>" . $this->conn->error;
        }
    }

    public function edit($id, $title, $content, $tags) {
        $sql = "UPDATE notes SET title = ?, content = ?, tags = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssi', $title, $content, $tags, $id);
        if ($stmt->execute() === TRUE) {
            return "Note updated successfully";
        } else {
            return "Error: " . $sql . "<br>" . $this->conn->error;
        }
    }

    public function delete($id) {
        $sql = "DELETE FROM notes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute() === TRUE) {
            return "Note deleted successfully";
        } else {
            return "Error: " . $sql . "<br>" . $this->conn->error;
        }
    }

    public function getAll() {
        $tid = getenv('ADMIN_ID');
        $sql = <<<SQL
        SELECT notes.id, notes.user_id, notes.title, notes.content, notes.tags, notes.created_at 
        FROM notes 
        JOIN users ON notes.user_id = users.id 
        WHERE users.tid = ?;
        SQL;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $tid);
        $stmt->execute();
        $result = $stmt->get_result();

        $notes = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['content'] = isset($row['content']) ? $this->replaceTags($row['content']) : '';
                $notes[] = $row;
            }
        }
        return $notes;
    }

    private function replaceTags($content) {
        // Замена тегов на нужные форматы
        $tags = [
            'bold' => ['<b>', '</b>'],
            'italic' => ['<i>', '</i>'],
            'code' => ['<code>', '</code>'],
            'pre' => ['<pre>', '</pre>'],
            'underline' => ['<u>', '</u>'],
            'strikethrough' => ['<s>', '</s>'],
            'spoiler' => ["<span class='tg-spoiler'>", '</span>'],
            'url' => ['<a>', '</a>']
        ];

        // Замена тегов <a> на <u>
        $content = str_replace(['<a>', '</a>'], ['<u>', '</u>'], $content);

        // Удаление неизвестных тегов, кроме тех, что указаны выше
        $allowedTags = array_merge(...array_values($tags));
        $content = strip_tags($content, implode('', $allowedTags));

        return $content;
    }
}
?>
