<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;
use losthost\telle\Bot;

/**
 * Description of AnotherFinalHandler
 *
 * @author drweb
 */
class HandlerFinalCallback extends \losthost\telle\abst\AbstractHandlerCallback {
    
    const IS_FINAL = true;
    
    protected function check(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {
        return true;
    }

    protected function handle(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool {
        
        try {
            Bot::$api->sendMessage(
                Bot::$chat->id,
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
