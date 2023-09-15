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
class CallbackHandler extends Handler {

    public function isFinal() : bool {
        return false;
    }
    
    protected function init() : void {
        // nothing to
    }
    
    protected function check(\TelegramBot\Api\Types\Update &$update) : bool {
        $callback_query = $update->getCallbackQuery();
        return (bool)$callback_query;
    }

    protected function handle(\TelegramBot\Api\Types\Update &$update) : bool {
        $callback_query = $update->getCallbackQuery();

        Bot::$api->sendMessage(
            $update->getCallbackQuery()->getMessage()->getChat()->getId(),
            'You have pressed <b>'. $update->getCallbackQuery()->getData(). '</b> button.',
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
