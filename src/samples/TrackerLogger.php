<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;

/**
 * Description of LoggerTracker
 *
 * @author drweb
 */
class TrackerLogger extends \losthost\DB\DBTracker {
   
    public function track(\losthost\DB\DBEvent $event) {
        $event_type = $event->typeName($event->type);
        $class = get_class($event->object);
        Bot::logComment("TrackerLogger: Event $event_type came from $class.");
    }
}
