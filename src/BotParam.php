<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of BotParam
 *
 * @author drweb_000
 */
class BotParam extends \losthost\DB\DBObject {

    const TABLE_NAME = 'bot_params';
    
    const SQL_CREATE_TABLE = <<<END
            CREATE TABLE IF NOT EXISTS %TABLE_NAME% (
                name varchar(100) NOT NULL,
                value varchar(256),
                PRIMARY KEY (name)
            ) COMMENT = 'v1.0.0'
            END;
    
    public function __construct($name, $default=null) {
        parent::__construct('name = ?', $name, true);
        if ($this->isNew()) {
            $this->name = $name;
            $this->value = $default;
            $this->write();
            return;
        } 
        
        if ($this->value === null) {
            $this->value = $default;
            if ($this->isModified()) {
                $this->write();
            }
        }
        
    }    
    
}
