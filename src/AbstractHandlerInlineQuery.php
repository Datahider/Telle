<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of AbstractHandlerInlineQuery
 *
 * @author drweb
 */
abstract class AbstractHandlerInlineQuery extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\Inline\InlineQuery &$inline_query) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\Inline\InlineQuery &$inline_query) : bool;
    
}
