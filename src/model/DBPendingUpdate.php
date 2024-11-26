<?php

namespace losthost\telle\model;
use losthost\DB\DB;

class DBPendingUpdate extends \losthost\DB\DBObject {
    
const METADATA = [
    'id' => 'bigint UNSIGNED NOT NULL',
    'data' => 'text(10240) NOT NULL',
    'locked_till' => 'int UNSIGNED NOT NULL',
    'worker' => 'int UNSIGNED',
    'PRIMARY KEY' => 'id'
];    

    public static function tableName() {
        return DB::$prefix. 'telle_pending_updates';
    }
    
    public function __construct(int|\TelegramBot\Api\Types\Update $update, $worker=null, $max_processing_time=null) {
        if (is_a($update, \TelegramBot\Api\Types\Update::class)) {
            if ($worker === null || $max_processing_time === null) {
                throw new \Exception('You must pass $worker and $max_processing_time for new pending update');
            }
            parent::__construct(['id' => $update->getUpdateId()], true);
            $this->data = $update;
            $this->locked_till = time() + $max_processing_time;
            $this->worker = $worker;
            $this->write();
        } else {
            parent::__construct(['id' => $update]);
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
    
    static function truncate() {
        
        $sql = 'TRUNCATE TABLE [telle_pending_updates]';
        $sth = DB::prepare($sql);
        $sth->execute();
    }
}
