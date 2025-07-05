<?php

namespace losthost\telle\abst;

abstract class AbstractHandlerMessageReactionCount extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\MessageReactionCountUpdated &$message_reaction_count) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\MessageReactionCountUpdated &$message_reaction_count) : bool;
    
}
