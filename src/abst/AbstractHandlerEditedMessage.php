<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\abst;

/**
 * Description of AbstractHandlerEditedChannelPost
 *
 * @author drweb
 */
abstract class AbstractHandlerEditedMessage extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\Message &$edited_message) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\Message &$edited_message) : bool;
    
}
