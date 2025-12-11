<?php

use losthost\telle\Bot;
use losthost\telle\Worker;

require_once 'vendor/autoload.php';

$worker_thread_id = %s;

Bot::setup($worker_thread_id);

Worker::new($worker_thread_id)->run();