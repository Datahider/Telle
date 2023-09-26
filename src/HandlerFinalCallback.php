<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of AnotherFinalHandler
 *
 * @author drweb
 */
class HandlerFinalCallback extends AbstractHandlerCallback {
    
    public function isFinal() : bool {
        return true;
    }
    
    protected function init() : void {
        // nothing to
    }

    protected function check(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {
        return true;
    }

    protected function handle(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {
        
        try {
            Bot::$api->sendMessage(
                Env::$chat->id,
                'This another final handler is for callback queries',
            );
        } catch (\TelegramBot\Api\HttpException $ex) {
            if ($ex->getCode() != 403) {
                throw $ex;
            }
        }
        
        return true;
    }
    
}
