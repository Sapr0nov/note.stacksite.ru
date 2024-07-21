<?php

namespace App\Classes\dbController;

use App\Classes\tg_Bot\TgBotClass;
use mysqli;

class User
{
    private $MYSQLI;

    private $TABLE = 'users';
    private $TABLE_MSGS = 'messages';
    private $TABLE_STATUS = 'user_statuses';
    
    public function __construct(mysqli $mysqli) {
        $this->MYSQLI = $mysqli;
        $sessionTime = 3600;
        $query = "SET SESSION wait_timeout = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('i', $sessionTime);
        $stmt->execute();
        $stmt->close();
    }

    public function init() {
        $response = "";
        $queries = [
            "CREATE TABLE IF NOT EXISTS " . $this->TABLE . " (
                id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
                tid bigint NOT NULL UNIQUE,
                user_name varchar(255) NULL DEFAULT '',
                first_name varchar(255) NULL DEFAULT '',
                last_name varchar(255) NULL DEFAULT '',
                created_at datetime NULL DEFAULT CURRENT_TIMESTAMP,
                status int NULL DEFAULT 0
            );",
            "CREATE TABLE IF NOT EXISTS " . $this->TABLE_MSGS . " (
                id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
                msg_id bigint NOT NULL UNIQUE,
                user_id bigint NULL,
                chat_id bigint NULL,
                text text NULL DEFAULT '',
                created_at datetime NULL DEFAULT CURRENT_TIMESTAMP
            );",
            "DROP TABLE IF EXISTS " . $this->TABLE_STATUS . ";",
            "CREATE TABLE IF NOT EXISTS " . $this->TABLE_STATUS . " (
                id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                status varchar(255) NULL DEFAULT ''
            );",
            "INSERT INTO " . $this->TABLE_STATUS . " (`id`, `status`) VALUES(0, 'main_menu')"
        ];

        foreach ($queries as $query) {
            try {
                $this->MYSQLI->query($query);
                $response .= "Таблица создана или обновлена.\r\n";
            } catch (\Exception $e) {
                $response .= "Ошибка создания таблицы: " . $e->getMessage() . "\r\n";
            }
        }

        return $response;
    }

    public function add($tid, $user_name = '', $first_name = '', $last_name = '', $status = 0) {
        if ($user_id = $this->checkUser($tid)) {
            return $user_id;
        }

        $query = "INSERT INTO `" . $this->TABLE 
        . "` (`tid`, `first_name`, `last_name`, `user_name`)" 
        . "VALUES(?, ?, ?, ?)";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('isss', $tid, $first_name, $last_name, $user_name);

        try {
            $stmt->execute();
            $stmt->close();
            return $this->MYSQLI->insert_id;
        } catch (\Exception $e) {
            $stmt->close();
            return null;
        }
    }

    public function checkUser($tid) {
        $query = "SELECT `id` FROM `" . $this->TABLE . "` WHERE `tid` = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('i', $tid);

        try {
            $stmt->execute();
            $result = $stmt->get_result();
            $obj = $result->fetch_object();
            $stmt->close();
            return $obj ? $obj->id : null;
        } catch (\Exception $e) {
            $stmt->close();
            return null;
        }
    }
    
    public function getStatus($uid) {
        $query = "SELECT `u`.`status` as 'code', `s`.`status` as 'value' 
                  FROM `" . $this->TABLE . "` u 
                  INNER JOIN `" . $this->TABLE_STATUS . "` s ON `u`.`status` = `s`.`id`
                  WHERE `u`.`id` = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('i', $uid);

        try {
            $stmt->execute();
            $stmt->close();
            return $stmt->get_result()->fetch_object();
        } catch (\Exception $e) {
            $stmt->close();
            return null;
        }
    }

    public function setStatus($uid, $status) {
        $statusObject = is_int($status) ? $this->checkStatus($status) : $this->checkStatus(null, $status);

        if (is_null($statusObject)) {
            return null;
        }

        $query = "UPDATE `" . $this->TABLE . "` SET `status` = ? WHERE `id` = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('ii', $statusObject->code, $uid);

        try {
            $stmt->execute();
            $stmt->close();
            return $statusObject;
        } catch (\Exception $e) {
            $stmt->close();
            return null;
        }
    }

    private function checkStatus($sid = null, $status = null) {
        $query = "SELECT `id` as 'code', `status` as 'value' FROM `" . $this->TABLE_STATUS . "` WHERE ";
        if (!is_null($sid)) {
            $query .= "`id` = ?";
        } elseif (!is_null($status)) {
            $query .= "`status` = ?";
        }

        $stmt = $this->MYSQLI->prepare($query);
        if (!is_null($sid)) {
            $stmt->bind_param('i', $sid);
        } elseif (!is_null($status)) {
            $stmt->bind_param('s', $status);
        }

        try {
            $stmt->execute();
            $stmt->close();
            return $stmt->get_result()->fetch_object();
        } catch (\Exception $e) {
            $stmt->close();
            return null;
        }
    }

    private function msg_save($chat_id, $user_id, $message_id, $text) {
        $tgBot = new TgBotClass(getenv('BOT_TOKEN'));
        $query = "INSERT INTO `" . $this->TABLE_MSGS
        . "` (`msg_id`, `user_id`, `chat_id`, `text`)" 
        . "VALUES(?, ?, ?, ?)";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('iiis', $message_id, $user_id, $chat_id, $text);
        $tgBot->msg_to_tg(getenv('ADMIN_ID'), 'DEBUG: ' . $user_id);

        try {
            $stmt->execute();
            $stmt->close();
            return $this->MYSQLI->insert_id;
        } catch (\Exception $e) {
            $stmt->close();
            $tgBot->msg_to_tg($chat_id, 'error'. json_encode($e->getMessage()));
            return null;
        }
    }

    private function msg_upd($chat_id, $message_id, $text) {
        $query = "UPDATE `" . $this->TABLE_MSGS . "` SET `text` = ? WHERE `msg_id` = ? AND `chat_id` = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('sii', $text, $message_id, $chat_id);

        try {
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (\Exception $e) {
            $stmt->close();
            return false;
        }
    }

    public function get_history($chat_id) {
        $history = [];
        $query = "SELECT `id`, `msg_id`, `user_id`, `chat_id`, `text` FROM `" . $this->TABLE_MSGS 
        . "` WHERE `chat_id` = ? ORDER BY id DESC LIMIT 20";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('i', $chat_id);

        try {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $message = [
                    'role' => $row['user_id'] == $chat_id ? 'user' : 'assistant',
                    'content' => $row['text'],
                ];
                $history[] = $message;
            }
            $stmt->close();
            return $history;
        } catch (\Exception $e) {
            $stmt->close();
            return null;
        }
    }

    public function msg_find($chat_id, $message_id) {
        $query = "SELECT `id`, `msg_id`, `user_id`, `chat_id`, `text` FROM `" . $this->TABLE_MSGS 
        . "` WHERE `msg_id` = ? AND `chat_id` = ? LIMIT 1";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('ii', $message_id, $chat_id);

        try {
            $stmt->execute();
            $stmt->close();
            return $stmt->get_result()->fetch_object();
        } catch (\Exception $e) {
            $stmt->close();
            return null;
        }
    }

    public function msgs_clear($tgBot, $chat_id) {

        $query = "SELECT `msg_id` FROM `" . $this->TABLE_MSGS . "` WHERE `chat_id` = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('i', $chat_id);

        try {
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = array_reverse($result->fetch_all(MYSQLI_ASSOC));
            foreach ($rows as $row) {
                $tgBot->delete_msg_tg($chat_id, $row['msg_id']);
            }
            $reply = $tgBot->msg_to_tg(
                chat_id: $chat_id,
                text: "Жду приказаний \xF0\x9F\x98\x8A",
                silent: true
            );

            $query = "DELETE FROM `" . $this->TABLE_MSGS . "` WHERE `chat_id` = ?";
            $stmt = $this->MYSQLI->prepare($query);
            $stmt->bind_param('i', $chat_id);
            $stmt->execute();

            self::save_reply($this, $reply);
            $stmt->close();
            return true;
        } catch (\Exception $e) {
            $stmt->close();
            $tgBot->msg_to_tg($chat_id, 'error'. json_encode($e->getMessage()));
            return false;
        }
    }

    public function all_usersId() {
        $query = "SELECT `tid` FROM `" . $this->TABLE . "` WHERE 1 = 1";
        $stmt = $this->MYSQLI->prepare($query);

        try {
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result;
        } catch (\Exception $e) {
            $stmt->close();
            return false;
        }
    }

    public function count_user_msgs($tid) {
        $query = "SELECT COUNT(`id`) as messages FROM `" . $this->TABLE_MSGS . "` WHERE `user_id` = ?";
        $stmt = $this->MYSQLI->prepare($query);
        $stmt->bind_param('i', $tid);

        try {
            $stmt->execute();
            $result = $stmt->get_result()->fetch_object()->messages;
            $stmt->close();
            return $result;
        } catch (\Exception $e) {
            $stmt->close();
            return false;
        }
    }

    public static function save_reply($users, $reply) {
        $replyTgBot = new TgBotClass(getenv('BOT_TOKEN'));
        $replyTgBot->get_data($reply);

        $msg_info = $replyTgBot->MSG_INFO;

        if (!isset($msg_info['chat_id']) || !isset($msg_info['message_id'])) {
            return;
        }

        $chat_id = $msg_info['chat_id'];
        $message_id = $msg_info['message_id'];
        $text = mysqli_real_escape_string($users->MYSQLI, $msg_info['text']);

        $isMsg = $users->msg_find($chat_id, $message_id);

        $msg_type = $msg_info['msg_type'];

        $uid = 1; // bot uid
        if (!$msg_type == 'bot_message') {
            $query = "SELECT id FROM " . $this->TABLE . " WHERE tid = ?";
            $stmt = $this->$MYSQLI->prepare($query);
            $stmt->bind_param('i', $msg_info['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $uid = $row['id'];
            }
            $result->free();
            $stmt->close();
        }

        if ($isMsg) {
            $users->msg_upd($chat_id, $message_id, $text);
        } else {
            $users->msg_save($chat_id, 2, $message_id, $text);
        }

        return $msg_info['message_id'];
    }
}
