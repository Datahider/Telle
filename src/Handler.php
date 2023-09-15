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

    protected $check_cache;

    public function __construct() {
        $this->initHandler();
    }
    
    abstract public function isFinal() : bool;
    abstract protected function init() : void;
    abstract protected function check(\TelegramBot\Api\Types\Update &$update) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\Update &$update) : bool;

    public function initHandler() {
        $this->check_cache = null;
        $this->init();
    }
    
    public function checkUpdate(\TelegramBot\Api\Types\Update &$update) {
        if ($this->check_cache === null) {
            $this->check_cache = $this->check($update);
        }
        
        return $this->check_cache; 
    }

    public function handleUpdate(\TelegramBot\Api\Types\Update &$update) {
        if (!$this->checkUpdate($update)) {
            return false;
        }
        return $this->handle($update);
    }
}
