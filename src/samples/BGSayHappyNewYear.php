<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\samples;

/**
 * Description of BGSayHello
 *
 * @author drweb
 */
class BGSayHappyNewYear extends \losthost\telle\abst\AbstractBackgroundProcess {
    
    public function run() {
        \losthost\telle\Bot::$api->sendMessage($this->param, "Happy New Year!");
    }

}
