<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of FinalHandler
 *
 * @author drweb
 */
class HandlerFinal extends AbstractHandlerMessage {

    protected function init() : void {
        // nothing to
    }

    public function isFinal(): bool {
        return true;
    }
    
    protected function check(\TelegramBot\Api\Types\Message &$message) : bool {
        if (!$message) {
            return false;
        }
        return (bool)$message->getText();
    }

    protected function handle(\TelegramBot\Api\Types\Message &$message) : bool {
        Bot::$api->sendMessage(
            Env::$chat->id,
            'Final handlers are always called.',
        );
        
        return true;
    }
}
