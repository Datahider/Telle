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
class DBUser extends \losthost\DB\DBObject {

const METADATA = [
    'id' => 'bigint UNSIGNED NOT NULL',
    'is_bot' => 'tinyint UNSIGNED NOT NULL',
    'first_name' => 'varchar(256) NOT NULL',
    'last_name' => 'varchar(256)',
    'username' => 'varchar(256)',
    'language_code' => 'varchar(10)',
    'is_premium' => 'tinyint(1)',
    'added_to_attachment_menu' => 'tinyint(1)',
    'can_join_groups' => 'tinyint(1)',
    'can_read_all_group_messages' => 'tinyint(1)',
    'support_inline_queries' => 'tinyint(1)',
    'PRIMARY KEY' => 'id'
];    
    
    public static function tableName() {
        return DB::$prefix. 'telle_users';
    }
    
    public function __construct(\TelegramBot\Api\Types\User &$user) {
        parent::__construct(['id' => $user->getId()], true);
        $this->is_bot = (int)$user->isBot();
        $this->first_name = $user->getFirstName();
        $this->last_name = $user->getLastName();
        $this->username = $user->getUsername();
        $this->language_code = $user->getLanguageCode();
        $this->is_premium = (int)$user->getIsPremium();
        $this->added_to_attachment_menu = (int)$user->getAddedToAttachmentMenu();
        $this->can_join_groups = (int)$user->getCanJoinGroups();
        $this->can_read_all_group_messages = (int)$user->getCanReadAllGroupMessages();
        $this->support_inline_queries = (int)$user->getSupportsInlineQueries();
            
        if ($this->isModified()) {
            $this->write();
        }
    }
}
