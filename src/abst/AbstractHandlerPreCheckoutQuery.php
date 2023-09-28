<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\abst;

/**
 * Description of AbstractHandlerPreCheckoutQuery
 *
 * @author drweb
 */
abstract class AbstractHandlerPreCheckoutQuery extends AbstractHandler {

    abstract protected function check(\TelegramBot\Api\Types\Payments\Query\PreCheckoutQuery &$pre_checkout_query) : bool;
    abstract protected function handle(\TelegramBot\Api\Types\Payments\Query\PreCheckoutQuery &$pre_checkout_query) : bool;
    
}
