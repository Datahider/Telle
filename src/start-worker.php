<?php

namespace losthost\telle;

require_once "vendor/autoload.php";
require_once 'etc/config-defaults.php';

Bot::init();

$worker = new Worker($argv[1]);
$worker->run();