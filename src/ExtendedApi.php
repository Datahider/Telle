<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\ArrayOfUpdates;

/**
 * Description of ExtendedApi
 *
 * @author drweb_000
 */
class ExtendedApi extends BotApi {

    public function getUpdates($offset = 0, $limit = 100, $timeout = 0, array $allowed_updates = null) {

        if (is_null($allowed_updates)) {
            return parent::getUpdates($offset, $limit, $timeout);
        }
        
        $updates = ArrayOfUpdates::fromResponse($this->call('getUpdates', [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout,
            'allowed_updates' => json_encode($allowed_updates),
        ]));

        return $updates;
    }
}
