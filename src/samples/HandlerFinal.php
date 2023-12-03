<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;
use losthost\telle\Bot;

/**
 * Description of FinalHandler
 *
 * @author drweb
 */
class HandlerFinal extends \losthost\telle\abst\AbstractHandlerMessage {
    
    const IS_FINAL = true;
    
    protected function check(\TelegramBot\Api\Types\Message &$message) : bool {
        if (!$message) {
            return false;
        }
        return (bool)$message->getText();
    }

    protected function handle(\TelegramBot\Api\Types\Message &$message) : bool {
        Bot::$api->sendMessage(
            Bot::$chat->id,
            'Final handlers are always called.',
        );
        
        return true;
    }
}
