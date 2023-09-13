<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of LoggerHandler
 *
 * @author drweb
 */
class LoggerHandler extends Handler {
    
    protected function check(\TelegramBot\Api\Types\Update &$update) {
        $message = $update->getMessage();
        if (!$message) {
            return false;
        }
        return (bool)$message->getText();
    }

    protected function handle(\TelegramBot\Api\Types\Update &$update) {
        error_log($update->getMessage()->getText());
    }
}
