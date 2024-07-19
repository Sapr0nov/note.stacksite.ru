<?php

namespace App\Classes\tg_Bot;

class TgBotClass
{
    public $BOT_TOKEN;
    public $DATA;
    public $MSG_INFO = [];

    function __construct($token) {
        $this->BOT_TOKEN = $token;
    }

    public function register_web_hook($path) {
        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/setWebhook?url=' . $path,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
        ];

        curl_setopt_array($ch, $ch_post);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function get_data($dataInput) {
        $this->DATA = json_decode($dataInput, true);
        $this->MSG_INFO = $this->DATA['message'];
        if (isset($this->DATA['update_id'])) {
            $this->MSG_INFO['update_id'] = $this->DATA['update_id'];
        }

        if (isset($this->DATA['message'])) {
            $message = $this->DATA['message'];
            $this->MSG_INFO['user_id'] = $message['from']['id'] ?? 0;
            $this->MSG_INFO['chat_id'] = $message['chat']['id'] ?? 0;
            $this->MSG_INFO['message_id'] = $message['message_id'];
            $this->MSG_INFO['from_first_name'] = $message['from']['first_name'] ?? '';
            $this->MSG_INFO['from_last_name'] = $message['from']['last_name'] ?? '';
            $this->MSG_INFO['from_username'] = $message['from']['username'] ?? '';
            $this->MSG_INFO['type'] = $message['chat']['type'];
            $this->MSG_INFO['text'] = $message['text'] ?? '';

            if (isset($message['voice'])) {
                $this->MSG_INFO['msg_type'] = 'voice';
                $this->MSG_INFO['voice'] = $message['voice'];
                $this->MSG_INFO['voice']['rel_url'] = $this->downloadVoiceFile($message['voice']['file_id']);
            } else {
                $this->MSG_INFO['msg_type'] = 'message';
            }

            if (isset($message['sticker'])) {
                $this->MSG_INFO['text'] = 'sticker';
                $this->MSG_INFO['sticker'] = [
                    'emoji' => $message['sticker']['emoji'] ?? '',
                    'name' => $message['sticker']['set_name'] ?? ''
                ];
            }

            $this->MSG_INFO['name'] = $this->MSG_INFO['from_first_name'] . ' ' . $this->MSG_INFO['from_last_name'] ?: $this->MSG_INFO['from_username'];

            if (isset($message['text']) && isset($message['entities'])) {
                $this->MSG_INFO['command'] = $this->getCommand($message['text'], $message['entities']);
            }
            return;

            $this->MSG_INFO['entities'] = $message['entities'] ?? '';

            if (!empty($message['entities'])) {
                $this->MSG_INFO['text_html'] = $this->convertEntities($message['text'], $message['entities']);
            } else {
                $this->MSG_INFO['text_html'] = $message['text'];
            }
        }

        if (isset($this->DATA['callback_query'])) {
            $callback = $this->DATA['callback_query'];
            $this->MSG_INFO['msg_type'] = 'callback';
            $this->MSG_INFO['user_id'] = $callback['from']['id'] ?? 0;
            $this->MSG_INFO['chat_id'] = $callback['message']['chat']['id'] ?? 0;
            $this->MSG_INFO['message_id'] = $callback['message']['message_id'];
            $this->MSG_INFO['from_first_name'] = $callback['from']['first_name'] ?? '';
            $this->MSG_INFO['from_last_name'] = $callback['from']['last_name'] ?? '';
            $this->MSG_INFO['from_username'] = $callback['from']['username'] ?? '';
            $this->MSG_INFO['type'] = $callback['message']['chat']['type'];
            $this->MSG_INFO['text'] = $callback['data'];
            $this->MSG_INFO['date'] = $callback['message']['date'];
            $this->MSG_INFO['name'] = $this->MSG_INFO['from_first_name'] . ' ' . $this->MSG_INFO['from_last_name'] ?: $this->MSG_INFO['from_username'];
        }

        if (isset($this->DATA['result']['from']['is_bot']) && $this->DATA['result']['from']['is_bot']) {
            $this->MSG_INFO['msg_type'] = 'bot_message';
            $this->MSG_INFO['chat_id'] = $this->DATA['result']['chat']['id'] ?? 0;
            $this->MSG_INFO['user_id'] = $this->DATA['result']['chat']['id'] ?? 0;
            $this->MSG_INFO['text'] = $this->DATA['result']['text'] ?? '';
            $this->MSG_INFO['message_id'] = $this->DATA['result']['message_id'];
            $this->MSG_INFO['name'] = 'bot';
        }
    }

    private function downloadVoiceFile($file_id) {
        $apiUrl = 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/getFile?file_id=' . $file_id;
        $response = file_get_contents($apiUrl);
        $responseArray = json_decode($response, true);
        $filePath = $responseArray['result']['file_path'];
        $url = 'https://api.telegram.org/file/bot' . $this->BOT_TOKEN . '/' . $filePath;
        $file = dirname(dirname(__DIR__)) . '/files/' . $file_id . '.oga';
        copy($url, $file);

        // Clean up old files
        $fileSystemIterator = new \FilesystemIterator(dirname(dirname(__DIR__)) . '/files');
        $now = time();
        foreach ($fileSystemIterator as $file) {
            if ($now - $file->getCTime() >= 60 * 60 * 2) { // 2 hours
                unlink($file->getPathname());
            }
        }

        return '/files/' . $file_id . '.oga';
    }

    public function msg_to_tg($chat_id, $text, $reply_markup = '', $reply_id = false, $silent = false) {
        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/sendMessage',
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chat_id,
                'parse_mode' => 'HTML',
                'text' => $text,
                'reply_markup' => $reply_markup,
                'reply_to_message_id' => $reply_id,
                'disable_notification' => $silent,
            ]
        ];

        curl_setopt_array($ch, $ch_post);
        $reply_txt = curl_exec($ch);
        curl_close($ch);

        return $reply_txt;
    }

    public function send_audio_tg($msg_id, $chat_id, $reply_id, $audio, $caption = 'audio') {
        $request_url = "https://api.telegram.org/bot{$this->BOT_TOKEN}/sendAudio";

        $data = [
            'chat_id' => $chat_id,
            'audio' => new \CURLFile($audio),
            'caption' => $caption,
            'reply_to_message_id' => $reply_id,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $reply_txt = curl_exec($ch);
        curl_close($ch);

        return $reply_txt;
    }

    public function update_msg_tg($msg_id, $chat_id, $text, $reply_markup = '', $silent = false) {
        $request_url = "https://api.telegram.org/bot{$this->BOT_TOKEN}/editMessageText?chat_id={$chat_id}&message_id={$msg_id}&text=" . urlencode($text);
        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => $request_url,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'editMessageReplyMarkup' => TRUE,
                'reply_markup' => $reply_markup,
                'chat_id' => $chat_id,
                'parse_mode' => 'HTML'
            ]
        ];
        curl_setopt_array($ch, $ch_post);
        $reply_txt = curl_exec($ch);
        curl_close($ch);

        return $reply_txt;
    }

    public function delete_msg_tg($chat_id, $msg_id) {
        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/deleteMessage?chat_id=' . $chat_id . '&message_id=' . $msg_id,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chat_id,
                'parse_mode' => 'HTML'
            ]
        ];

        curl_setopt_array($ch, $ch_post);
        curl_exec($ch);
        curl_close($ch);
    }

    public function debug($output) {
        $SITE_DIR = dirname(__FILE__) . '/';
        $file_message = file_get_contents($SITE_DIR . 'debug.txt');
        $output = json_encode($output);
        file_put_contents($SITE_DIR . 'debug.txt',  $file_message . PHP_EOL . 'output = ' . $output);
    }

    public function keyboard($arr) {
        return json_encode([
            'keyboard' => $arr,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }

    public function inline_keyboard($arr) {
        return json_encode([
            'inline_keyboard' => $arr,
        ]);
    }

    private function getCommand(string $str, $arr = null): array {
        $result = [
            'is_command' => false,
            'command' => null,
            'args' => null
        ];

        if (!is_array($arr) || is_null($str)) {
            return $result;
        }

        foreach ($arr as $value) {
            if ($value['type'] == 'bot_command') {
                $offset = $value['offset'];
                $length = $value['length'];
                $result['is_command'] = true;
                $result['command'] = trim(substr($str, ($offset + 1), $length));
                $result['args'] = trim(substr($str, $offset + $length, strlen($str) - $offset - $length));
            }
        }
        return $result;
    }

    private function convertEntities(string $str, array $arr): string {
        if (!is_array($arr)) {
            return $str;
        }
        $result_str = $this->filterString($str);
        $arr_string = mb_str_split($str, 1);

        $arr = array_reverse($arr);
        foreach ($arr as $value) {
            $offset = $value['offset'];
            $length = $value['length'];
            $type_switch = $value['type'];
            $type = match ($type_switch) {
                'bold' => ['<b>', '</b>'],
                'italic' => ['<i>', '</i>'],
                'code' => ['<code>', '</code>'],
                'pre' => ['<pre>', '</pre>'],
                'underline' => ['<u>', '</u>'],
                'strikethrough' => ['<s>', '</s>'],
                'spoiler' => ["<span class='tg-spoiler'>", '</span>'],
                'url' => ['<a>', '</a>'],
                default => null,
            };
            if (!is_null($type)) {
                array_splice($arr_string, $offset + $length, 0, $type[1]);
                array_splice($arr_string, $offset, 0, $type[0]);
            }
        }
        $result_str = implode('', $arr_string);

        return $result_str;
    }

    private function filterString($input) {
        $filtered_string = strip_tags($input);
        $filtered_string = htmlspecialchars($filtered_string, ENT_NOQUOTES, 'UTF-8', false);

        return $filtered_string;
    }
}

?>
