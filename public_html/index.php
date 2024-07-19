<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'autoload.php';

use Controllers\NoteController;

$controller = new NoteController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_note':
                $controller->addNote();
                break;
            case 'edit_note':
                $controller->editNote();
                break;
            case 'delete_note':
                $controller->deleteNote();
                break;
        }
    }
} else {
    $controller->listNotes();
}
?>
