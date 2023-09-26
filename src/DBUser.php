<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle;

/**
 * Description of DBUser
 *
 * @author drweb
 */
class DBUser extends \losthost\DB\DBObject {
    
    const TABLE_NAME = 'users';
    
    const SQL_CREATE_TABLE = <<<END
            CREATE TABLE IF NOT EXISTS %TABLE_NAME% (
                id bigint UNSIGNED NOT NULL,
                is_bot tinyint UNSIGNED NOT NULL,
                first_name varchar(256) NOT NULL,
                last_name varchar(256),
                username varchar(256),
                language_code varchar(10),
                is_premium tinyint UNSIGNED,
                added_to_attachment_menu tinyint UNSIGNED,
                can_join_groups tinyint UNSIGNED,
                can_read_all_group_messages tinyint UNSIGNED,
                support_inline_queries tinyint UNSIGNED,
                PRIMARY KEY (id)
            ) COMMENT = 'v1.0.0'
            END;
    
    public function __construct(\TelegramBot\Api\Types\User &$user) {
        parent::__construct('id = ?', $user->getId(), true);
        if ($this->isNew()) {
            $this->id = $user->getId();
        }
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
