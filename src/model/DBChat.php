<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle\model;
use losthost\DB\DB;

/**
 * Description of DBUser
 *
 * @author drweb
 */
class DBChat extends \losthost\DB\DBObject {
    
const METADATA = [
    'id' => 'bigint NOT NULL',
    'type' => 'varchar(20) NOT NULL',
    'title' => 'varchar(256)',
    'username' => 'varchar(256)',
    'first_name' => 'varchar(256)',
    'last_name' => 'varchar(256)',
    'is_forum' => 'tinyint UNSIGNED NOT NULL',
    'PRIMARY KEY' => 'id'
];    
    
    public static function tableName() {
        return DB::$prefix. 'telle_chats';
    }
    
    public function __construct(\TelegramBot\Api\Types\Chat &$chat) {
        parent::__construct(['id' => $chat->getId()], true);
        $this->type = $chat->getType();
        $this->title = $chat->getTitle();
        $this->username = $chat->getUsername();
        $this->first_name = $chat->getFirstName();
        $this->last_name = $chat->getLastName();
        $this->is_forum = (int)$chat->getIsForum();
            
        if ($this->isModified()) {
            $this->write();
        }
    }
}
