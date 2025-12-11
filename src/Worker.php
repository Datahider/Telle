<?php

namespace losthost\telle;

use losthost\telle\model\DBPendingUpdate;
use losthost\DB\DBView;
use losthost\DB\DBValue;
use losthost\DB\DB;
use losthost\DB\DBList;
use losthost\BackgroundProcess\BackgroundProcess;

class Worker {
    
    const LOCK_IDLE = 'idle';
    const LOCK_TIMEOUT = 120;
    const LOCK_GET = 'SELECT GET_LOCK(?, ?) AS locked';
    const LOCK_RELEASE = 'SELECT RELEASE_LOCK(?) AS released';
    
    const SELECT_CONVERSATIONS = 'SELECT DISTINCT conversation_id FROM [telle_pending_updates] ORDER BY RAND()';
    
    protected string $id;
    protected ?string $conversation_id;
    
    public function __construct(string $id) {
        $this->id = $id;
        $this->conversation_id = null;
    }
    
    static function new(string $id) : static {
        return new static($id);
    }
    
    public function run() {
        
        Bot::logComment("$this->id started");
        while (true) {
            $this->getIdleLock();       // Пытается получить IDLE_LOCK. Не получил за 2 минуты -> die;
            
            Bot::logComment("$this->id got LOCK_IDLE");
            
            $this->findJob();           // Устанавливает $this->conversation_id
                                        // Делает GET_LOCK $this->conversation_id
                                        // Отпускает IDLE_LOCK
            
            $this->startIdleWorker();   // Запускает нового дежурного
            
            $this->doJob();             // Обрабатывает апдейты из выбранного чата
        }
    }

    protected function getIdleLock() {
        $lock = new DBValue(self::LOCK_GET, [self::LOCK_IDLE, self::LOCK_TIMEOUT]);
        if ($lock->locked == 0) {
            Bot::logComment("$this->id couldn't obtain LOCK_IDLE. Dieing.");
            die;
        }
    }
    
    protected function findJob() {
        while (true) {
            $now_in_queue = $this->getConversations(); // Получает массив conversation_id
            foreach ($now_in_queue as $conversation_id) {
                Bot::logComment("$this->id trying lock $conversation_id");
                $lock = new DBValue(self::LOCK_GET, [$conversation_id, 0]);
                if ($lock->locked > 0) {
                    Bot::logComment("$this->id locked $conversation_id");
                    $this->conversation_id = $conversation_id;
                    new DBValue(self::LOCK_RELEASE, self::LOCK_IDLE);
                    Bot::logComment("$this->id released LOCK_IDLE");
                    Bot::logComment("$this->id conversation_id = $this->conversation_id");
                    return;
                }
            }
            usleep(100000);
        }
    }

    protected function startIdleWorker() {
        $worker_template = file_get_contents(__DIR__. '/worker-template.php');
        if ($worker_template === false) {
            throw new \RuntimeException("Can't open worker-template.php file");
        }
        BackgroundProcess::create($worker_template)
                    ->run(uniqid('w', true));        
    }
    
    protected function doJob() {
        
        while ($queued_update = $this->getQueuedUpdate()) {
            try {
                Bot::processHandlers($queued_update->data);
            } catch (\Throwable $e) {
                // Ничего не делаем. Забиваем на этот апдейт раз он кривой
                Bot::logException($e);
            }
            $queued_update->delete();
        }
        
        new DBValue(self::LOCK_RELEASE, $this->conversation_id);
        Bot::logComment("$this->id released $this->conversation_id");
        $this->conversation_id = null;
    }
 
    protected function getConversations() {
        
        $sth = DB::prepare(self::SELECT_CONVERSATIONS);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    protected function getQueuedUpdate() {
        $list_of_one = new DBList(DBPendingUpdate::class, 'conversation_id = ? ORDER BY id LIMIT 1', $this->conversation_id);
        return $list_of_one->next();
    }
}
