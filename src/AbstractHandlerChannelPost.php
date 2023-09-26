<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of AbstractHandlerChannelPost
 *
 * @author drweb
 */
abstract class AbstractHandlerChannelPost extends AbstractHandler {
    
    abstract protected function check(\TelegramBot\Api\Types\Message &$message) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\Message &$message) : bool;
    
}
