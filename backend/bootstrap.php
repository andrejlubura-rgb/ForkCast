<?php

// Registers an autoloader for the backend class folders, so classes are never required manually.
spl_autoload_register(function ($className) {
    foreach (['dao', 'services', 'middleware'] as $folder) {
        $file = __DIR__ . '/' . $folder . '/' . $className . '.php';

        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
