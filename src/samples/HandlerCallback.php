<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;
use losthost\telle\Bot;

/**
 * Description of CallbackHandler
 *
 * @author drweb
 */
class HandlerCallback extends \losthost\telle\abst\AbstractHandlerCallback {

    protected function check(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {
        return (bool)$callback_query;
    }

    protected function handle(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {

        HandlerMessageAfterButton::showPrompt($callback_query->getMessage()->getMessageId());

        try {
            Bot::$api->answerCallbackQuery($callback_query->getId(), 'Put additional info here.');
        } catch (\Exception $e) {
            /// Nothing to do (old query)
        }
        
        return true;
    }
}
