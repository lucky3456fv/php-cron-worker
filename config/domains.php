<?php
return [
    // Each domain maps to an array of task definitions
    // name => [ 'type' => class or alias, 'interval' => seconds, ...params ]
    'example.com' => [
        [ 'type' => 'http_ping', 'interval' => 60, 'path' => '/' ],
        [ 'type' => 'php_script', 'interval' => 300, 'script' => __DIR__ . '/../scripts/sample_task.php' ],
    ],
    'example.org' => [
        [ 'type' => 'http_ping', 'interval' => 300, 'path' => '/' ],
    ],
];

