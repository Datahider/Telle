<?php

namespace losthost\telle;

require_once "vendor/autoload.php";
require_once 'etc/config-defaults.php';

Bot::setup();

$process_class = $argv[1];

if ( isset($argv[2]) ) {
    $process = new $process_class($argv[2]);
    error_log("Starting $process_class with parameter $argv[2]");
} else {
    $process = new $process_class();
    error_log("Starting $process_class without parameter");
}

$process->run();