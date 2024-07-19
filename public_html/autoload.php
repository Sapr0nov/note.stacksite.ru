<?php
function autoload($className) {
    $directories = [
        'Classes/',
        'tg_helper/Classes/'
    ];

    foreach ($directories as $directory) {
        $file = $directory . str_replace('\\', '/', $className) . '.php';
        if (file_exists($file)) {
            include $file;
            return;
        }
    }

    throw new Exception("File not found for class: " . $className);
}

spl_autoload_register('autoload');
?>
