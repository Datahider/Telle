<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle;

/**
 * Description of Handler
 *
 * @author drweb_000
 */
abstract class AbstractHandler {

    protected $check_cache;

    public function __construct() {
        $this->initHandler();
    }
    
    abstract public function isFinal() : bool;
    abstract protected function init() : void;

    public function initHandler() {
        $this->check_cache = null;
        $this->init();
    }
    
    public function checkUpdate(\TelegramBot\Api\BaseType &$data) : bool {

        if ($this->check_cache === null) {
            $this->check_cache = $this->check($data);
        }
        
        return $this->check_cache; 
    }

    public function handleUpdate(
            \TelegramBot\Api\Types\CallbackQuery 
            |\TelegramBot\Api\Types\Message
            |\TelegramBot\Api\Types\Inline\ChosenInlineResult
            |\TelegramBot\Api\Types\Inline\InlineQuery
            |\TelegramBot\Api\Types\Poll
            |\TelegramBot\Api\Types\PollAnswer
            |\TelegramBot\Api\Types\Payments\Query\PreCheckoutQuery
            |\TelegramBot\Api\Types\Payments\Query\ShippingQuery
            |\TelegramBot\Api\Types\ChatMemberUpdated
            |\TelegramBot\Api\Types\ChatJoinRequest &$data) : bool {

        if (!$this->checkUpdate($data)) {
            return false;
        }
        return $this->handle($data);
    }
}
