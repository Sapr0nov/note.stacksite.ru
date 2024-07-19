<?php

namespace App\Classes\ChatGPT;

class ChatGPTClass
{
    private $API_KEY;
    private $ORGANIZATION;
    private $BASE_URL = 'https://api.openai.com/v1';
    public $CHAT_END_POINT = '/chat/completions';
    public $MODEL = 'gpt-3.5-turbo';
    public $USER = 'user'; // ??
    public $VOICE = 'echo'; // alloy, echo, fable, onyx, nova, and shimmer


    function __construct($config) {
        $this->API_KEY = $config->token;
        $this->ORGANIZATION = ($config->organization) ? $config->organization : null;
        $this->CHAT_END_POINT = ($config->endPoint) ? $config->endPoint : '/chat/completions';
        $this->MODEL = ($config->model) ? $config->model : 'gpt-3.5-turbo';
        $this->USER = ($config->user) ? $config->user : 'user';
        $this->VOICE = ($config->voice) ? $config->voice : 'echo';
    }

    public function transcribe($file) {
        $url = $this->BASE_URL . $this->CHAT_END_POINT;
        $data = array(
            'model' => $this->MODEL,
            'file' => curl_file_create($file)
            );
        $content_type = "multipart/form-data";
        $headers = array(
            "Content-type: $content_type",
            "Authorization: Bearer $this->API_KEY",
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    public function ask($message, $history = []) {
        $url = $this->BASE_URL . $this->CHAT_END_POINT;
        if ($this->MODEL == 'gpt-3.5-turbo') {
            array_push($history, array('role' => 'user', 'content' => $message) );
            $data = array(
                'model' => $this->MODEL,
                'messages' => $history,
                );
            $content_type = "application/json";
        }
        if ($this->MODEL == 'tts-1') {
            $data = array(
                'model' => $this->MODEL,
                "input" => $message,
                "voice" => $this->VOICE,
                );
            $content_type = "application/json";
        }

        $options = array(
            'http' => array(
                'header' => "Content-Type: $content_type\r\n" .
                            "Authorization: Bearer $this->API_KEY\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
            ),
        );

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        return $response;
    }
}
?>