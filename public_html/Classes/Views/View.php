<?php
namespace Views;

class View {
    public function render($template, $data = []) {
        extract($data);
        include __DIR__ . "/../Templates/$template.php";
    }
}
?>
