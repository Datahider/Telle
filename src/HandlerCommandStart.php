<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of StartCommandHandler
 *
 * @author drweb
 */
class HandlerCommandStart extends AbstractHandlerMessage {
    
    public function isFinal() : bool {
        return false;
    }
    
    protected function init() : void {
        // nothing to
    }

    protected function check(\TelegramBot\Api\Types\Message &$message) : bool {
        if (!$message) {
            return false;
        }
        return (bool)preg_match("/^\/[Ss][Tt][Aa][Rr][Tt](\s.*)*$/", $message->getText());
    }

    protected function handle(\TelegramBot\Api\Types\Message &$message) : bool {
        Bot::$api->sendMessage(
            Env::$chat->id,
            "Hi! This is an example /start command handler"
        );
        return true;
    }

}
