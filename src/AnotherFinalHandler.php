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
class AnotherFinalHandler extends Handler {
    
    public function isFinal() : bool {
        return true;
    }
    
    protected function init() : void {
        // nothing to
    }

    protected function check(\TelegramBot\Api\Types\Update &$update) : bool {
        $message = $update->getMessage();
        if (!$message) {
            return true;
        }
        return !(bool)$message->getText();
    }

    protected function handle(\TelegramBot\Api\Types\Update &$update) : bool {
        
        Bot::$api->sendMessage(
            $this->getChatId($update),
            'This another final handler is for non-text updates',
        );
        
        return true;
    }
    
    protected function getChatId(\TelegramBot\Api\Types\Update &$update) {
        if ($update->getMessage()) {
            return $update->getMessage()->getChat()->getId();
        } elseif ($update->getCallbackQuery()) {
            return $update->getCallbackQuery()->getMessage()->getChat()->getId();
        }
    }
}
