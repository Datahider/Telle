<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;
use losthost\telle\Bot;

/**
 * Description of StartCommandHandler
 *
 * @author drweb
 */
class HandlerCommandStart extends \losthost\telle\abst\AbstractHandlerCommand {
    
    const COMMAND = 'start';
    
    protected function handle(\TelegramBot\Api\Types\Message &$message) : bool {
        
        $text = "Hi! This is an example /start command handler.";
        
        if ($this->args) {
            $text .= "\nArgs given: <b>$this->args</b>";
        } else {
            $text .= "\nNo args given to this command.";
        }
        Bot::$api->sendMessage(
            Bot::$chat->id,
            $text, 'HTML'
        );
        return true;
    }

}
