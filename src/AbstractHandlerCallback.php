<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of AbstractHandlerCallback
 *
 * @author drweb
 */
abstract class AbstractHandlerCallback extends AbstractHandler {
    
    abstract protected function check(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\CallbackQuery &$callback_query) : bool;
    
}
