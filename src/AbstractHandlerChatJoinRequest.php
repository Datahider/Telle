<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of AbstractHandlerChatJoinRequest
 *
 * @author drweb
 */
abstract class AbstractHandlerChatJoinRequest extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\ChatJoinRequest &$chat_join_request) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\ChatJoinRequest &$chat_join_request) : bool;
    
}
