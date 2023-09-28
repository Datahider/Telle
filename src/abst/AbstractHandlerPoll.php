<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\abst;

/**
 * Description of AbstractHandlerPoll
 *
 * @author drweb
 */
abstract class AbstractHandlerPoll extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\Poll &$poll) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\Poll &$poll) : bool;
    
}
