<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of HandlerMessageAfterButton
 *
 * @author drweb
 */
class HandlerMessageAfterButton extends AbstractHandlerMessage {
    //put your code here
    protected function check(\TelegramBot\Api\Types\Message &$message): bool {
        self::unsetPriority();
        $text = $message->getText();
        
        if ($text && preg_match("/^[^\/]/", $text)) {
            return true;
        } else {
            return false;
        }
    }

    protected function handle(\TelegramBot\Api\Types\Message &$message): bool {
        Bot::$api->sendMessage(Env::$chat->id, "This is a special processing of text message after pressing PRESS_ME button");
        return true;
    }

    protected function init(): void {
        
    }

    public function isFinal(): bool {
        return false;
    }
    
    static public function showPrompt($message_id) {
        static::setPriority(['message_id' => $message_id]);
        Bot::$api->sendMessage(Env::$chat->id, __("There is a special processing of text message afer pressing \"Press me\" button. So please send me something interesting."));
    }
}
