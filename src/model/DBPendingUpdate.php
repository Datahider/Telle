<?php

namespace losthost\telle\model;
use losthost\DB\DB;
use TelegramBot\Api\Types\Update;
use losthost\DB\DBObject;

class DBPendingUpdate extends DBObject {
    
    const METADATA = [
        'id' => 'bigint UNSIGNED NOT NULL',
        'data' => 'text(10240) NOT NULL',
        'conversation_id' => 'VARCHAR(64) NOT NULL DEFAULT ""',
        'PRIMARY KEY' => 'id',
        'INDEX idx_conversation' => 'conversation_id'
    ];    

    const CONVERSATION_ID_DELIMITER = ':';
    
    public static function tableName() {
        return DB::$prefix. 'telle_pending_updates';
    }
    
    static public function add(Update &$update) : static {
        $me = new static(['id' => $update->getUpdateId()], true);
        if ($me->isNew()) {
            $me->data = $update;
            $me->conversation_id = static::getConversationId($update);
            $me->write();
        }
        return $me;
    }
    
    static protected function getConversationId(Update &$update) {
        if ($update->getCallbackQuery()) {
            return  $update->getCallbackQuery()->getMessage()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . ($update->getCallbackQuery()->getMessage()->getMessageThreadId()??'0');
        } elseif ($update->getChannelPost()) {
            return $update->getChannelPost()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getChatBoost()) {
            return $update->getChatBoost()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getChatBoostRemoved()) {
            return $update->getChatBoostRemoved()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getChatJoinRequest()) {
            return $update->getChatJoinRequest()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getChatMember()) {
            return $update->getChatMember()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getChosenInlineResult()) {
            return $update->getChosenInlineResult()->getFrom()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getEditedChannelPost()) {
            return $update->getEditedChannelPost()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getEditedMessage()) {
            return $update->getEditedMessage()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . ($update->getEditedMessage()->getMessageThreadId()??'0');
        } elseif ($update->getInlineQuery()) {
            return $update->getInlineQuery()->getFrom()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getMessage()) {
            return $update->getMessage()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . ($update->getMessage()->getMessageThreadId()??'0');
        } elseif ($update->getMessageReaction()) {
            return $update->getMessageReaction()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getMessageReactionCount()) {
            return $update->getMessageReactionCount()->getChat()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getMyChatMember()) {
            return $update->getMyChatMember()->getChat()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getPoll()) {
                    '0'
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getPollAnswer()) {
            return $update->getPollAnswer()->getUser()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getPreCheckoutQuery()) {
            return $update->getPreCheckoutQuery()->getFrom()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } elseif ($update->getShippingQuery()) {
            return $update->getShippingQuery()->getFrom()->getId()
                    . self::CONVERSATION_ID_DELIMITER
                    . '0';
        } else {
            throw new \InvalidArgumentException("Unknown update type");
        }
    }
    
    public function __get($name) {
        if ($name == 'data') {
            return unserialize($this->__data['data']);
        }
        return parent::__get($name);
    }
    
    public function __set($name, $value) {
        if ($name == 'data') {
            $this->__data['data'] = serialize($value);
            return;
        }
        parent::__set($name, $value);
    }
    
    static function truncate() {
        
        $sql = 'TRUNCATE TABLE [telle_pending_updates]';
        $sth = DB::prepare($sql);
        $sth->execute();
    }
}
