<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of AbstractHandlerChosenInlineResult
 *
 * @author drweb
 */
abstract class AbstractHandlerChosenInlineResult extends AbstractHandler {
    
    abstract protected function check(\TelegramBot\Api\Types\Inline\ChosenInlineResult &$chosen_inline_result) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\Inline\ChosenInlineResult &$chosen_inline_result) : bool;
    
}
