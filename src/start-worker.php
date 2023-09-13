<?php

namespace losthost\telle;

require_once 'etc/config.php';
require_once "vendor/autoload.php";

Bot::init();

$worker = new Worker($argv[1]);
$worker->run();