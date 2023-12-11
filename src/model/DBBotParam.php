<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\model;
use losthost\DB\DB;

/**
 * Description of BotParam
 *
 * @author drweb_000
 */
class DBBotParam extends \losthost\DB\DBObject {

    const METADATA = [
        'name'          => 'varchar(100) NOT NULL',
        'value'         => 'varchar(256)',
        'PRIMARY KEY'   => 'name'
    ];
    
    public static function tableName() {
        return DB::$prefix. 'telle_bot_params';
    }
    
    public function __construct($name, $default=null) {
        parent::__construct(['name' => $name], true);
        if ($this->isNew()) {
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
    
    public function __set($name, $value) {
        if ($this->name == $value) {
            return;
        }
        parent::__set($name, $value);
        $this->write();
    }
    
}
