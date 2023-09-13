<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of EchoHandler
 *
 * @author drweb
 */
class EchoHandler extends Handler {

    protected function check(\TelegramBot\Api\Types\Update &$update) {
        $message = $update->getMessage();
        if (!$message) {
            return false;
        }
        return (bool)$message->getText();
    }

    protected function handle(\TelegramBot\Api\Types\Update &$update) {
        
        $text = $update->getMessage()->getText();
        if ($text == 'ping') {
            
            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                [['text' => 'Press me!', 'callback_data' => 'PRESS_ME']]
            ]);
            
            Bot::$api->sendMessage(
                $update->getMessage()->getChat()->getId(),
                "pong",
                "HTML",
                false,
                null,
                $keyboard
            );
        } else {
            Bot::$api->sendMessage(
                $update->getMessage()->getChat()->getId(),
                "Your message: $text\nTry to send <b>ping</b>",
                "HTML"
            );
        }
        
        $this->setLast();
    }
}
