<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of Worker
 *
 * @author drweb_000
 */
class Worker {
    
    protected $id;
    protected $init;
    
    protected $handlers;
    protected $update;
    protected $db_update;

    public function __construct($id) {

        $this->id = $id;
        
        Bot::$api->setCurlOption(CURLOPT_CAINFO, Bot::getCaInfo());
        $this->init = false;
    }

    public function init() {

        $this->init = true;
        
        error_log("Worker $this->id is initialized.");
        
    }
    
    public function run() {
        
        if (!$this->init) {
            $this->init();
        }

        error_log("Worker $this->id is started.");

        while (1) {
            $this->getUpdate();
            $this->processUpdate();
            
        }
    }
    
    protected function getUpdate() {

        $line = readline();
        if ($line === false) {
            die("Worker $this->id: Can't read next update. Dieing.\n");
        }
        
        $this->db_update = new Update((int)$line);
        $this->update = $this->db_update->data;
    }
    
    protected function processUpdate() {

        $this->db_update->state = Update::STATE_PROCESSING;
        $this->db_update->write();

        Bot::processHandlers($this->update);
        
        $this->db_update->state = Update::STATE_FINISHED;
        $this->db_update->locked_till = null;
        $this->db_update->write();
    }
    
}
