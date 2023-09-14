<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of LoggerTracker
 *
 * @author drweb
 */
class LoggerTracker extends \losthost\DB\DBTracker {
   
    public function track(\losthost\DB\DBEvent $event) {
        $event_type = $event->typeName($event->type);
        $class = get_class($event->object);
        error_log("LoggerTracker: Event $event_type came from $class.");
    }
}
