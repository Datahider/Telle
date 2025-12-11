<?php

use losthost\telle\Bot;
use losthost\telle\Worker;

require_once 'vendor/autoload.php';
Bot::setup();

Worker::new(%s)->run();