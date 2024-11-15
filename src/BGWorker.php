<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

use losthost\telle\Bot;
use losthost\telle\model\DBPendingUpdate;

/**
 * Description of Worker
 *
 * @author drweb_000
 */
class BGWorker extends abst\AbstractBackgroundProcess {
    
    protected $id;
    protected $init;
    
    protected $handlers;

    public function __construct($id=null) {

        if ( $id === null ) {
            $id = 0;
        }
        $this->id = $id;
        
        $this->init = false;
        
    }

    public function init() {

        $this->init = true;
        
        Bot::logComment("Worker $this->id is initialized", __FILE__, __LINE__);
        
    }
    
    public function run() {
        
        if (!$this->init) {
            $this->init();
        }

        Bot::logComment("Worker $this->id is started", __FILE__, __LINE__);

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
