<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle;

/**
 * Description of Handler
 *
 * @author drweb_000
 */
abstract class Handler {

    protected bool $last;
    protected $check_cache;
    protected $last_update_id;


    public function __construct() {
        $this->last = false;
        $this->check_cache = null;
        $this->last_update_id = null;
    }
    
    abstract protected function check(\TelegramBot\Api\Types\Update &$update);
    abstract protected function handle(\TelegramBot\Api\Types\Update &$update);

    public function checkUpdate(\TelegramBot\Api\Types\Update &$update) {
        $current_update_id = $update->getUpdateId();
        if ($this->last_update_id != $current_update_id) {
            $this->check_cache = $this->check($update);
            $this->last_update_id = $current_update_id;
            $this->last = false;
        }
        
        return $this->check_cache; 
    }

    public function handleUpdate(\TelegramBot\Api\Types\Update &$update) {
        if (!$this->checkUpdate($update)) {
            return false;
        }
        return $this->handle($update);
    }
    
    public function isLast() {
        return $this->last;
    }
    
    public function setLast() {
        $this->last = true;
    }
    
    public function isFinal() {
        return false;
    }
    
}
