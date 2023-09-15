<?php

namespace losthost\telle;

class PendingUpdate extends \losthost\DB\DBObject {
    
    const TABLE_NAME = 'pending_updates';
    
    const SQL_CREATE_TABLE = <<<END
            CREATE TABLE IF NOT EXISTS %TABLE_NAME% (
                id bigint UNSIGNED NOT NULL,
                data text(10240) NOT NULL,
                locked_till int UNSIGNED NOT NULL,
                worker int UNSIGNED,
                PRIMARY KEY (id)
            ) COMMENT = 'v1.0.0';
            END;

    public function __construct(int|\TelegramBot\Api\Types\Update $update, $worker=null, $max_processing_time=null) {
        if (is_a($update, \TelegramBot\Api\Types\Update::class)) {
            if ($worker === null || $max_processing_time === null) {
                throw new \Exception('You must pass $worker and $max_processing_time for new pending update');
            }
            parent::__construct();
            $this->id = $update->getUpdateId();
            $this->data = $update;
            $this->locked_till = time() + $max_processing_time;
            $this->worker = $worker;
            $this->write();
        } else {
            parent::__construct('id = ?', $update, false);
        }
    }
    public function __get($name) {
        if ($name == 'data') {
            return unserialize($this->__data['data']);
        }
        return parent::__get($name);
    }
    
    public function __set($name, $value) {
        if ($name == 'data') {
            $this->__data['data'] = serialize($value);
            return;
        }
        parent::__set($name, $value);
    }
}
