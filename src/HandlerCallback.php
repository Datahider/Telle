<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of CallbackHandler
 *
 * @author drweb
 */
class HandlerCallback extends AbstractHandlerCallback {

    public function isFinal() : bool {
        return false;
    }
    
    protected function init() : void {
        // nothing to
    }
    
    protected function check(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {
        return (bool)$callback_query;
    }

    protected function handle(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {

        Bot::$api->sendMessage(
            Env::$chat->id,
            'You have pressed <b>'. $callback_query->getData(). '</b> button.',
            'HTML'
        );

        try {
            Bot::$api->answerCallbackQuery($callback_query->getId(), 'Put additional info here.');
        } catch (\Exception $e) {
            /// Nothing to do (old query)
        }
        
        return true;
    }
}
