<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle;

/**
 * Description of DBSession
 *
 * @author drweb
 */
class DBSession extends \losthost\DB\DBObject {

    const TABLE_NAME = 'sessions';
    
    const SQL_CREATE_TABLE = <<<END
            CREATE TABLE IF NOT EXISTS %TABLE_NAME% (
                id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
                user bigint UNSIGNED NOT NULL,
                chat bigint NOT NULL,
                message_thread_id bigint, 
                mode varchar(128),
                command varchar(128),
                state varchar(128),
                data text(4096),
                PRIMARY KEY (id)
            ) COMMENT = 'v1.0.0'
            END;
    
    const SQL_UPGRADE_FROM_1_0_0 = <<<END
            ALTER TABLE %TABLE_NAME% COMMENT = 'v1.0.1',
            ADD COLUMN priority_handler varchar(128);
            END;

    const FIELD_MODE    = 'mode';
    const FIELD_COMMAND = 'command';
    const FIELD_STATE   = 'state';
    const FIELD_DATA    = 'data';
    const FIELD_PRIORITY_HANDLER = 'priority_handler';
    
    public function __construct(int|DBUser $user, null|int|DBChat $chat=null, null|int $message_thread_id=null) {
        if (is_a($user, DBUser::class)) {
            $user = $user->id;
        }
        
        if ($chat === null) {
            $chat = $user;
        } elseif (is_a($chat, DBChat::class)) {
            $chat = $chat->id;
        }
        
        if ($message_thread_id === null) {
            parent::__construct(
                    'user = ? AND chat = ? AND message_thread_id IS NULL', 
                    [$user, $chat], 
                    true);
        } else {
            parent::__construct(
                    'user = ? AND chat = ? AND message_thread_id = ?', 
                    [$user, $chat, $message_thread_id], 
                    true);
        }
        
        $this->initNew($user, $chat, $message_thread_id);
    }
    
    protected function initNew($user, $chat, $message_thread_id) {
        if ($this->isNew()) {
            $this->user = $user;
            $this->chat = $chat;
            $this->message_thread_id = $message_thread_id;
            $this->write();
        }
    }
    
    public function get($name, $default=null) {
        $result = $this->$name;
        
        if ($result === null) {
            return $default;
        }
    }
    
    public function set($name, $value) {
        $this->$name = $value;
        $this->write();
    }
    
    public function __get($name) {
        if ($name == self::FIELD_DATA) {
            return unserialize(parent::__get($name));
        } else {
            return parent::__get($name);
        }
    }
    
    public function __set($name, $value) {
        if ($name == self::FIELD_DATA) {
            $value = serialize($value);
        }
        parent::__set($name, $value);
    }
}
