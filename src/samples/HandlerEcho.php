<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;
use losthost\telle\Bot;

/**
 * Description of EchoHandler
 *
 * @author drweb
 */
class HandlerEcho extends \losthost\telle\abst\AbstractHandlerMessage {

    protected function check(\TelegramBot\Api\Types\Message &$message) : bool {
        if (!$message) {
            return false;
        }
        return (bool)$message->getText();
    }

    protected function handle(\TelegramBot\Api\Types\Message &$message) : bool {
        
        $text = $message->getText();
        if ($text == 'ping') {
            
            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                [['text' => 'Press me!', 'callback_data' => 'PRESS_ME']]
            ]);
            
            Bot::$api->sendMessage(
                Bot::$chat->id,
                "pong",
                "HTML",
                false,
                null,
                $keyboard
            );
        } else {
            Bot::$api->sendMessage(
                Bot::$chat->id,
                "Your message: $text\nTry to send <b>ping</b>",
                "HTML"
            );
        }
        
        return true;
    }
}
