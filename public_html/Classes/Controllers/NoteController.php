<?php
namespace Controllers;

use Models\Note;
use Views\View;

class NoteController {
    private $note;
    private $view;

    public function __construct() {
        $this->note = new Note();
        $this->view = new View();
    }

    public function listNotes() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['note'])) {
            $this->note->add($_POST['note']);
        }
        $notes = $this->note->getAll();
        $this->view->render('notesList', ['notes' => $notes]);
    }

    public function addNote() {
        if ($_POST['title'] && $_POST['content']) {
            $this->note->add($_POST['title'], $_POST['content']);
        }
        header('Location: /');
    }

    public function editNote() {
        if ($_POST['id'] && $_POST['title'] && $_POST['content']) {
            $this->note->edit($_POST['id'], $_POST['title'], $_POST['content']);
        }
        header('Location: /');
    }

    public function deleteNote() {
        if ($_POST['id']) {
            $this->note->delete($_POST['id']);
        }
        header('Location: /');
    }

}
?>
