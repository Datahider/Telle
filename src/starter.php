<?php

namespace losthost\telle;

require_once "vendor/autoload.php";

Bot::setup();

$process_class = $argv[1];

if ( !empty($argv[2]) ) {
    $process = new $process_class($argv[2]);
    error_log("Starting $process_class with parameter $argv[2]");
} else {
    $process = new $process_class();
    error_log("Starting $process_class without parameter");
}

$process->run();