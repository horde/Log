<?php

use Horde\Test\Bootstrap;

$candidates = [
    dirname(__FILE__, 2) . '/vendor/autoload.php',
    dirname(__FILE__, 4) . '/autoload.php',
];
// Cover root case and library case
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
        break;
    }
}
// Use horde/test Bootstrap if available (optional dependency)
if (class_exists(Bootstrap::class)) {
    Bootstrap::bootstrap(dirname(__FILE__));
}
