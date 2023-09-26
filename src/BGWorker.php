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
class BGWorker extends AbstractBackgroundProcess {
    
    protected $id;
    protected $init;
    
    protected $handlers;

    public function __construct($id=null) {

        if ( $id === null ) {
            $id = 0;
        }
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
            $pending_update = $this->getUpdate();
            Bot::processHandlers($pending_update->data);
            $pending_update->delete();
        }
    }
    
    protected function getUpdate() {

        $line = readline();
        if ($line === false) {
            die("Worker $this->id: Can't read next update. Dieing.\n");
        }
        
        return new DBPendingUpdate((int)$line);
    }
    
}
