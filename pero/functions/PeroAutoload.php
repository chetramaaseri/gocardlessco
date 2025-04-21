<?php
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../pero/functions/';
    $file = $baseDir . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});