<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Classes/i18n.php';

use App\Classes\tg_Bot\TgBotClass;
use App\Classes\dbController\DbClass;
use App\Classes\dbController\User;
use App\Classes\dbController\Note;
use App\Classes\dbController\Notice;
use App\Classes\chatGPT\ChatGPTClass;
use App\Classes\Calendar\CalendarClass;

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Moscow');

$tgBot = new TgBotClass(getenv('BOT_TOKEN'));
$db = new DbClass(getenv('DB_SERVER'), getenv('DB_USER'), getenv('DB_PASSWORD'), getenv('DB_NAME'));

$calendar = new CalendarClass();
$users = new User($db->MYSQLI);
$notes = new Note($db->MYSQLI);
$notices = new Notice($db->MYSQLI);

$INIT = false;

if (isset($_GET['key']) && $_GET['key'] == getenv('APP_PASSWORD')) {
    handleWebhook($tgBot, $users);
    return;
}

if ($INIT) {
    handleInitialization($tgBot, $users, $notes, $notices);
    return;
}
$dataInput = file_get_contents('php://input');
$tgBot->get_data($dataInput);

$keyboard = generateKeyboards($tgBot, $calendar);


if (!isset($tgBot->MSG_INFO['user_id'])) {
    return;
}


$uid = $users->checkUser($tgBot->MSG_INFO['user_id']) ?: $users->add($tgBot->MSG_INFO['user_id'], $tgBot->MSG_INFO['from_first_name'], $tgBot->MSG_INFO['from_last_name'], $tgBot->MSG_INFO['from_username']);
$status = $users->getStatus($uid);


handleCallbacks($tgBot, $users, $notes, $keyboard);

// Если новое сообщение сохраняем - старое - выходим из скрипта (нужно если скрипт отрабатывает больше минуты)
if (!$users->msg_find($tgBot->MSG_INFO['chat_id'], $tgBot->MSG_INFO['message_id'])) {
    $users->save_reply($users, $dataInput);
} else {
    return;
}


if (!empty($tgBot->MSG_INFO['command']['is_command'])) {
    handleCommands($tgBot, $users, $notes, $keyboard, $uid);
    return;
}

handleStatusBasedActions($tgBot, $users, $notes, $notices, $status, $uid, $keyboard);

handleMessageActions($tgBot, $users, $notes, $notices, $keyboard, $uid);

if ($tgBot->MSG_INFO['msg_type'] == 'voice') {
    handleVoiceMessage($tgBot, $users, $dataInput);
}

$options = new stdClass;
$options->token = getenv('GPT_TOKEN');
$GPT = new ChatGPTClass($options);
searchGPT($tgBot, $GPT, $users, $tgBot->MSG_INFO['text']);
return;

function handleWebhook($tgBot, $users) {
    if ($_GET['msg'] != '') {
        $reply = $tgBot->msg_to_tg(getenv('ADMIN_ID'), $_GET['msg']);
        $users->save_reply($users, $reply);
        echo '{"status":"ok"}';
        return;
    }
    echo '{"status":"no messages"}';
}

function handleInitialization($tgBot, $users, $notes, $notices) {
    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    $result = $tgBot->register_web_hook($url);
    $response = json_decode($result);
    echo '<p>' . $response->description . '</p>';
    echo '<p>' . $users->init() . '</p>';
    echo '<p>' . $notes->init() . '</p>';
    echo '<p>' . $notices->init() . '</p>';
}

function generateKeyboards($tgBot, $calendar) {
    global $MENU1, $MENU_CALENDAR;
    return [
        'menu_search' => $tgBot->keyboard([[$MENU1['search'], $MENU1['add_note'], $MENU_CALENDAR['show']]]),
        'main_menu' => $tgBot->keyboard([[$MENU_CALENDAR['show']]]),
        'cal_days' => $tgBot->keyboard($calendar->generateCalendar()),
        'cal_days2' => $tgBot->keyboard([
            ['-', '1', '2', '3', '4', '5', '6'],
            ['7', '8', '[9*]', '10*', '11', '12', '13'],
            ['14', '15', '16', '17', '18', '19', '20'],
            ['21', '22', '23', '24', '25', '26', '27'],
            ['28', '29', '30', '-', '-', '-', '-']
        ]),
    ];
}

function handleCallbacks($tgBot, $users, $notes, $keyboard) {
    if ($tgBot->MSG_INFO['msg_type'] == 'callback') {
        $text = $tgBot->MSG_INFO['text'];
        if (stripos($text, 'txt2speach') !== false) {
            [$command, $arg] = explode(' ', $text);
            $msg = $users->msg_find($tgBot->MSG_INFO['chat_id'], $tgBot->MSG_INFO['message_id']);
            $options = new stdClass;
            $options->token = getenv('GPT_TOKEN');
            $options->model = 'tts-1';
            $options->endPoint = '/audio/speech';
            $options->voice = 'nova';
            $GPT = new ChatGPTClass($options);
            speachGPT($tgBot, $GPT, $users, $msg->text);
            return;
        }

        if (stripos($text, 'note_edit') !== false) {
            [$command, $arg] = explode(' ', $text);
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'callback pressed: ' . $command . ' arg: ' . $arg, $keyboard['menu_search']);
            User::save_reply($users, $reply);
            return;
        }

        if (stripos($text, 'note_delete') !== false) {
            [$command, $arg] = explode(' ', $text);
            $result = $notes->delete($uid, $arg);
            $response = ($result) ? 'Заметка удалена ' : 'Не удалось удалить заметку';
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], $response, $keyboard['menu_search']);
            User::save_reply($users, $reply);
            return;
        }
    }
}

function handleCommands($tgBot, $users, $notes, $keyboard, $uid) {
    $users->setStatus($uid, 'main_menu');
    $command = $tgBot->MSG_INFO['command']['command'];

    switch ($command) {
        case 'chatGPT':
        case 'gpt':
            $options = new stdClass;
            $options->token = getenv('GPT_TOKEN');
            $GPT = new ChatGPTClass($options);
            searchGPT($tgBot, $GPT, $users, $tgBot->MSG_INFO['command']['args']);
            break;
        case 'search':
        case 's':
            search($tgBot, $uid, $keyboard['inline_notes'], $users, $notes);
            break;
        case 'add_note':
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст заметки: ', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->setStatus($uid, 'add_note');
            break;
        case 'add_notice':
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст напоминания:', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->setStatus($uid, 'add_notice');
            break;
        case 'clear':
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Очищаем историю. Подождите..', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->msgs_clear($tgBot, $tgBot->MSG_INFO['chat_id']);
            break;
        default:
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Данная команда не поддерживается', $keyboard['menu_search']);
            User::save_reply($users, $reply);
    }
}

function handleStatusBasedActions($tgBot, $users, $notes, $notices, $status, $uid, $keyboard) {
    switch ($status->value) {
        case 'search':
            search($tgBot, $uid, $keyboard['menu_search'], $users, $notes, $tgBot->MSG_INFO['text']);
            break;
        case 'add_note':
            $notes->add($uid, $tgBot->MSG_INFO['text_html']);
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Принято возвращаемся в главное меню: ', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->setStatus($uid, 'main_menu');
            break;
        case 'add_notice':
            $notices->presave($uid, $tgBot->MSG_INFO['text_html']);
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите время напоминания в формате YYYY-MM-DD HH-MM или одно число - через сколько минут (5/n - повторить n раз): ', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->setStatus($uid, 'add_notice_time');
            break;
        case 'add_notice_time':
            $notices->add($uid, $tgBot->MSG_INFO['text_html']);
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Сохранено, возвращаемся в главное меню', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->setStatus($uid, 'main_menu');
            break;
    }
}

function handleMessageActions($tgBot, $users, $notes, $notices, $keyboard, $uid) {
    global $MENU1, $MENU_CALENDAR;
    if ($tgBot->MSG_INFO['msg_type'] == 'message') {
        $text = $tgBot->MSG_INFO['text'];
        if ($text == $MENU1['search']) {
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Укажите что ищем: ', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->setStatus($uid, 'search');
        } elseif ($text == $MENU1['add_note']) {
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст заметки: ', $keyboard['menu_search']);
            User::save_reply($users, $reply);
            $users->setStatus($uid, 'add_note');
        } elseif ($text == $MENU_CALENDAR['show']) {
            $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Выберите день ', $keyboard['cal_days']);
            User::save_reply($users, $reply);
        }
    }
}

function handleVoiceMessage($tgBot, $users, $dataInput) {
    $options = new stdClass;
    $options->token = getenv('GPT_TOKEN');
    $options->model = 'whisper-1';
    $options->endPoint = '/audio/transcriptions';
    $GPT = new ChatGPTClass($options);
    try {
        transcribeGPT($tgBot, $GPT, $users, $tgBot->MSG_INFO['voice']['rel_url']);
    } catch (\Exception $e) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Ошибка обработки сообщения ');
        User::save_reply($users, $reply);
    }
}

function searchGPT($tgBot, $GPT, $users, $question) {
    if ($question == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Вы не задали вопрос');
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], "⌛ loading...", reply_id: $tgBot->MSG_INFO['message_id']);
    $msg_id = User::save_reply($users, $reply);
    $history = $users->get_history($tgBot->MSG_INFO['chat_id']);
    
    $response = $GPT->ask($question, $history);
    $answerObj = json_decode($response);

    if ($answerObj) {
        $answer = $answerObj->choices[0]->message->content;
        $regEx = '/```(\w+)(.+?)```/is';
        $regEx2 = '/```(.+?)```/is';
        $answer = htmlspecialchars($answer, ENT_QUOTES);
        $answer = preg_replace($regEx, '<pre><code language="$1">$2</code></pre>', $answer);
        $answer = preg_replace($regEx2, '<code>$1</code>', $answer);
    } else {
        $answer = "не найдено";
    }
    $reply = $tgBot->update_msg_tg($msg_id, $tgBot->MSG_INFO['chat_id'], $answer, $tgBot->inline_keyboard([[["text" => "озвучить", "callback_data" => "txt2speach " . $msg_id]]]));
    User::save_reply($users, $reply);
}

function transcribeGPT($tgBot, $GPT, $users, $file) {
    if ($file == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Что-то пошло не так.. Не вижу аудио-файла');
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], "🦻 loading...", reply_id: $tgBot->MSG_INFO['message_id']);
    $msg_id = User::save_reply($users, $reply);
    try {
        $response = $GPT->transcribe(__DIR__ . $file);
        $answerObj = json_decode($response);
        $answer = ($answerObj) ? $answerObj->text : 'не найдено';
    } catch (Exception $e) {
        $err = $e->getMessage();
        $reply = $tgBot->update_msg_tg($msg_id, $tgBot->MSG_INFO['chat_id'], "Ошибка:" . json_encode($err));
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->update_msg_tg($msg_id, $tgBot->MSG_INFO['chat_id'], $answer);
    User::save_reply($users, $reply);

    $tgBot->get_data($reply);

    $GPT->MODEL = 'gpt-3.5-turbo';
    $GPT->CHAT_END_POINT = '/chat/completions';
    try {
        searchGPT($tgBot, $GPT, $users, $answer);
    } catch (Exception $e) {
        $err = "ошибка транскрибации";
        $reply = $tgBot->update_msg_tg($msg_id, $tgBot->MSG_INFO['chat_id'], $err);
        User::save_reply($users, $reply);
    }
}

function speachGPT($tgBot, $GPT, $users, $text) {
    if ($text == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Что-то пошло не так.. Не вижу текста');
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], "🗣 loading...", reply_id: $tgBot->MSG_INFO['message_id']);
    $msg_id = User::save_reply($users, $reply);
    $response = $GPT->ask($text);

    $reply = $tgBot->delete_msg_tg($tgBot->MSG_INFO['chat_id'], $msg_id);

    if ($response !== false) {
        $savePath = __DIR__ . '/files/speach' . $msg_id . '.mp3';
        file_put_contents($savePath, $response);
        $file = "https://stacksite.ru/assets/projects3/tg_helper/files/speach" . $msg_id . ".mp3";
        $reply = $tgBot->send_audio_tg($msg_id, $tgBot->MSG_INFO['chat_id'], $tgBot->MSG_INFO['message_id'], $file, "Сообщение озвучено");
    } else {
        $reply = $tgBot->update_msg_tg($msg_id, $tgBot->MSG_INFO['chat_id'], 'не удалось озвучить файл');
    }
    User::save_reply($users, $reply);
}

function search($tgBot, $tid, $keyboard, $users, $notes, $search_text = '') {
    if ($search_text == '' && $tgBot->MSG_INFO['command']['args'] == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Укажите что ищем: ');
        User::save_reply($users, $reply);
        $users->setStatus($tid, 'search');
        return;
    }
    if ($search_text == '') {
        $search_text = $tgBot->MSG_INFO['command']['args'];
    }
    $finded = $notes->search($tid, $search_text);
    if (count($finded) == 0) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Не удалось ничего найти');
        User::save_reply($users, $reply);
        $users->setStatus($tid, 'main_menu');
        return;
    }
    foreach ($finded as $key => $value) {
        $title = '<b>' . $value['title'] . '</b>';
        $content = $value['content'];
        $tags = $value['tags'];
        $date = $value['date'];
        $note_id = $value['note_id'];
        $keyboard = $tgBot->inline_keyboard([[
            [
                "text" => "теги",
                "callback_data" => "note_tags " . $note_id
            ],
            [
                "text" => "изменить",
                "callback_data" => "note_edit " . $note_id
            ],
            [
                "text" => "удалить",
                "callback_data" => "note_delete " . $note_id
            ]
        ]]);
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], ($key + 1) . ': ' . $title . "\r\n" . $content, $keyboard);
        User::save_reply($users, $reply);
   }
}

?>
