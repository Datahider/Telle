<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of Starter
 *
 * @author drweb_000
 */
class WorkerHandle {
    
    protected string $cmd;
    protected string $id;
    
    protected $process;


    public function __construct(string $cmd, string $id='') {
        $this->cmd = $cmd;
        $this->id = $id;
    }
    
    public function run() {
        $process = popen($this->cmd, 'w');
        
        if ($process === false) {
            throw new \Exception("Can't start thread.", -10002);
        }
        
        $this->process = $process;
    }
    
    public function send($data, $depth=0) {

        if ($depth == \losthost\telle\Bot::param('worker_restart_tryes', 10)) {
            throw new \Exception("Maximum worker restart tryes count reached.", -10009);
        }
        
        $result = fwrite($this->process, "$data\n");

        if ($result === false && !$depth) {
            error_log("Can't send data to worker. Restarting...");
            $this->run();
            $this->send($data, $depth+1);
        } elseif ($result === false) {
            error_log("Can't send data to worker. Sleeping...");
            sleep(Bot::param('worker_restart_sleep', 5));
            error_log("Restarting...");
            $this->run();
            $this->send($data, $depth+1);
        }
    }
}
