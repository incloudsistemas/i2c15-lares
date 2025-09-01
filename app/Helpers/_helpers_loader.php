<?php

foreach (glob(__DIR__ . '/*.php') as $filename) {
    if (basename($filename) !== '_helpers_loader.php') {
        require_once $filename;
    }
}
