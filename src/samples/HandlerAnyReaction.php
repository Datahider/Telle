<?php

namespace losthost\telle\samples;

use losthost\telle\Bot;
use losthost\telle\abst\AbstractHandlerMessageReaction;

class HandlerAnyReaction extends AbstractHandlerMessageReaction {
    
    protected function check(\TelegramBot\Api\Types\MessageReactionUpdated &$message_reaction): bool {
        return true;
    }

    protected function handle(\TelegramBot\Api\Types\MessageReactionUpdated &$message_reaction): bool {
        Bot::$api->sendMessage(Bot::$chat->id, 'Спасибо за вашу реакцию!');
        return true;
    }
}
