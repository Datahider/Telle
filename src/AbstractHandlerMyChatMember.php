<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of AbstractHandlerMyChatMember
 *
 * @author drweb
 */
abstract class AbstractHandlerMyChatMember extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\ChatMemberUpdated &$chat_member) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\ChatMemberUpdated &$chat_member) : bool;
    
}
