<?php

namespace losthost\telle\abst;

abstract class AbstractHandlerMessageReaction extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\MessageReactionUpdated &$message_reaction) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\MessageReactionUpdated &$message_reaction) : bool;
    
}
