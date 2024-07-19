<?php

namespace App\Classes\dbController;

class Notice
{
    private $MYSQLI;
    private $TABLE = 'notices';
    /**
     * id       bigint
     * user_id  bigint
     * title    string 255 
     * content  text
     * date_remind datatime
     * status   string 100
     */
    function __construct($mysqli) {
        $this->MYSQLI = $mysqli;
        date_default_timezone_set('Europe/Moscow');
    }

    function init() {
        $response = "";
        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE . " (
            id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint NOT NULL,
            title varchar(255) NULL DEFAULT '',
            content text NULL,
            date_remind datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status varchar(100) NULL DEFAULT ''
        );";
        try {
            $this->MYSQLI->query($query);
            $response .= "Таблица " . $this->TABLE . " создана.\r\n";
        } catch (\Exception $e) {
            $response .= "Ошибка создания таблицы " . $this->TABLE . "\r\n";
        }

        return $response;
    }


    function presave($user_id, $text) {
        $title = '';
        $content = $text;
        $query = "DELETE FROM `" . $this->TABLE . "` WHERE `status` = 'draft' AND `user_id` = '" . $user_id . "';";
        try {
            $this->MYSQLI->query($query);
        } catch (\Exception $e) {
            return false;
        }

        $query = "INSERT INTO `" . $this->TABLE . "` (user_id, title, content, status) VALUES (" . $user_id . ", '" . $title . "', '" . $content . "', 'draft');";
        try {
            $this->MYSQLI->query($query);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    function add($user_id, $date) {
        $date_arr = explode("/",$date);
        $interval = $date_arr[0];
        $repeat = $date_arr[1] ?? 1; 
        // читаем сохраненные до этого заголовок и содержимое напоминания
        $query = "SELECT `title`, `content` FROM `" . $this->TABLE . "` " .
        " WHERE `user_id` = " . $user_id . " AND `status` = 'draft';";
        try {
            $result = $this->MYSQLI->query($query);
        } catch (\Exception $e) {
            return false;
        }
        if($result){
            $row = $result->fetch_object();
            $title = $row->title;
            $content = $row->content;
            $result->close();
        }else{
            return false;
        }
        while ($repeat >= 1) {
            $date_str = (intval($interval) == $interval) ? date('Y-m-d H:i:00', time() + $interval * $repeat * 60) : $interval;
            $repeat--;
            $query = "INSERT INTO `" . $this->TABLE . 
            "` (user_id, title, content, date_remind, status) VALUES (" 
            . $user_id . ", '" . $title . "', '" . $content . "' , '" . $date_str . "', 'active');";
            
            try {
                $this->MYSQLI->query($query);
            } catch (\Exception $e) {
                return false;
            }    
        }
        return true;
    }


    function search($user_id, $str) {
         $search_str = $this->MYSQLI->real_escape_string($str);
        $query = "SELECT `id` as note_id, title, content, created_at as date, tags FROM `" . $this->TABLE . "`"
        . " WHERE `user_id` = '" . $user_id . "' AND ( CONVERT(`title` USING utf8) LIKE '%" . $search_str . "%' OR CONVERT(`content` USING utf8) LIKE '%" . $search_str . "%' OR CONVERT(`tags` USING utf8) LIKE '%" . $search_str . "%') ";
        try {
            $result = $this->MYSQLI->query($query);
        }catch(\Exception $e) {
            $result = false;
        }
        if (!$result) {
            return null;
        }
        $array = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        return $array;     
    }

}

?>