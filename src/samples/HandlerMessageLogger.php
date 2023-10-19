<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;
use losthost\telle\Bot;

/**
 * Description of HandlerMessageLogger
 *
 * @author drweb
 */
class HandlerMessageLogger extends \losthost\telle\abst\AbstractHandlerMessage {
    
    protected $text;
    
    protected function check(\TelegramBot\Api\Types\Message &$message): bool {
        $this->text = $message->getText();
        return (bool)$this->text;
    }

    protected function handle(\TelegramBot\Api\Types\Message &$message): bool {
        error_log($this->text);
        return false;
    }
    
}
