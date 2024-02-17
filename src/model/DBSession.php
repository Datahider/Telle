<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle\model;
use losthost\DB\DB;

/**
 * Description of DBSession
 *
 * @author drweb
 */
class DBSession extends \losthost\DB\DBObject {

const METADATA = [
    'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT',
    'user' => 'bigint UNSIGNED NOT NULL',
    'chat' => 'bigint NOT NULL',
    'message_thread_id' => 'bigint', 
    'mode' => 'varchar(128)',
    'command' => 'varchar(128)',
    'state' => 'varchar(128)',
    'data' => 'text(4096)',
    'priority_handler' => 'varchar(128)',
    'PRIMARY KEY' => 'id'
];    
    
    const FIELD_MODE    = 'mode';
    const FIELD_COMMAND = 'command';
    const FIELD_STATE   = 'state';
    const FIELD_DATA    = 'data';
    const FIELD_PRIORITY_HANDLER = 'priority_handler';
    
    public static function tableName() {
        return DB::$prefix. 'telle_sessions';
    }
    
    public function __construct(null|int|DBUser $user, null|int|DBChat $chat=null, null|int $message_thread_id=null) {
        
        if (is_a($chat, DBChat::class)) {
            $chat = $chat->id;
        }
        if (is_a($user, DBUser::class)) {
            $user = $user->id;
        }
        
        if ($user === null) {
            $user = $chat;
        }
        
        if ($chat === null) {
            $chat = $user;
        }
        
        if ($chat === null) {
            throw new \Exception("Params \$user and \$chat can't both be NULL.");
        }
        
        parent::__construct(['user' => $user, 'chat' => $chat, 'message_thread_id' => $message_thread_id], true);
        if ($this->isNew()) {
            $this->write();
        }
    }
    
    public function get($name, $default=null) {
        $result = $this->$name;
        
        if ($result === null) {
            return $default;
        }
        return $result;
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
