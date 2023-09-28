<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\abst;

/**
 * Description of BackgroundProcess
 *
 * @author drweb
 */
abstract class AbstractBackgroundProcess {
    protected $param;
    abstract public function run();
    
    public function __construct($param=null) {
        $this->param = $param;
    }
}
