<?php
$LANG = isset($LANG) ? $LANG : 'ru';
if ($LANG == 'ru') {
    $ERROR['err'] = "Ошибка:";
    
    $BTNS['start'] = "Начать";

    $MENU1['search'] = "Найти";
    $MENU1['add_note'] = "Добавить заметку";
    $MENU1['remove_note'] = "Удалить заметку";

    $MENU_CALENDAR['show'] = "Планер";

    $RETURNTXT['selectAction'] = "Выберите действие:";
}

if ($LANG == 'en') {
    $ERROR['err'] = "Error:";

    $BTNS['start'] = "Start";

    $RETURNTXT['selectAction'] = "Select an action:";
}

?>