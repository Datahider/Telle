<?php

namespace losthost\telle\abst;

use Exception;

abstract class AbstractHandlerCommand extends AbstractHandlerMessage {
    
    const COMMAND = null;
    
    protected string $args;
    
    public function __construct() {
        parent::__construct();
        if (!is_string(static::COMMAND) || empty(static::COMMAND)) {
            throw new Exception('You must define const COMMAND in class '. static::class);
        } elseif (static::COMMAND != strtolower(static::COMMAND)) {
            throw new Exception('You must define const COMMAND in lowercase in class '. static::class);
        }
    }
    
    protected function check(\TelegramBot\Api\Types\Message &$message): bool {
        $m = [];
        if (preg_match("/^\/([a-zA-Z0-9_]+)\s*(.*)$/s", $message->getText(), $m) && (strtolower($m[1]) == static::COMMAND)) {
            $this->args = $m[2];
            return true;
        }
        return false;
    }
    
}
