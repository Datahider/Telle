<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of Update
 *
 * @author drweb_000
 */
class Update extends \losthost\DB\DBObject {
    
    const STATE_NEW = 0;
    const STATE_PROCESSING = 1;
    const STATE_FINISHED = 255;
    
    const TABLE_NAME = 'updates';
    
    const SQL_CREATE_TABLE = <<<END
            
            CREATE TABLE IF NOT EXISTS %TABLE_NAME% (
                id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
                data text(10240) NOT NULL,
                description varchar(1024),
                locked_till int UNSIGNED,
                worker int UNSIGNED,
                state tinyint UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) COMMENT = 'v1.0.0';
            END;
    
    public function __construct(int | \TelegramBot\Api\Types\Update $update) {
        
        if (is_int($update)) {
            parent::__construct('id = ?', $update);
            if ($this->isNew()) {
                throw new \Exception("Update with id $update not found");
            }
        } else {
            parent::__construct();
            $this->data = $update;
            $this->state = 0;
            $this->write();
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
}
