<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of Bot
 *
 * @author drweb
 */
class Bot {

    // Default values. Change via global $config array
    protected static int $workers_count = 5;            // number of worker processes for standalone mode

    public static int $worker_restart_tryes = 10;       // number of tryes to restart worker
    public static int $worker_restart_sleep = 5;        // number of seconds before restart worker

    protected static int $max_processing_time = 15;     // maximum time for worker to process an update
    protected static int $get_updates_timeout = 40;     // getUpdates timeout parameter
    protected static string $token = '';                // bot token
    
    protected static string $db_host = 'localhost';     // database host
    protected static string $db_user = '';              // database user
    protected static string $db_pass = '';              // database password
    protected static string $db_name = '';              // database name
    protected static string $db_prefix = 'telle_';      // database table prefix
    
    // path to the cacert file for curl                 //
    protected static string $cacert = __DIR__. '/cacert.pem';

    // command to start worker process in background    //
    protected static string $worker_start_cmd = 'start /b php '. __DIR__. '/start-worker.php'; // Windows
    //protected static string $worker_start_cmd = 'php '. __DIR__. '/start-worker.php >/dev/null 2>&1 &'; // *nix

    protected static $handlers = [                  // array of Handlers. Override by global $trackers 
        '\\losthost\\telle\\LoggerHandler',                 // Logs text messages to error log
        '\\losthost\\telle\\StartCommandHandler',           // Handles /start command
        '\\losthost\\telle\\EchoHandler',                   // Sends echo text back to user
        '\\losthost\\telle\\CallbackHandler',               // Handles callback queries
        '\\losthost\\telle\\FinalHandler',                  // Sends final message
        '\\losthost\\telle\\AnotherFinalHandler',           // This is for non-text updates
    ];  
    
    protected static $trackers = [                  // Array of DBTrackers. Override by global $trackers
        \losthost\telle\LoggerTracker::class => [   // Class name as array key
            'events' => [                               // Use \losthost\DB\DBEvent::ALL_EVENTS for all
                \losthost\DB\DBEvent::AFTER_INSERT,
                \losthost\DB\DBEvent::AFTER_UPDATE,
                \losthost\DB\DBEvent::AFTER_DELETE,
            ],
            'objects' => [                              // Use '*' for all
                BotParam::class, 
                PendingUpdate::class,
            ],
                                                        // Add more if you need
        ]
    ];
    /// End of config 
    
    
    protected static $workers = [];
    protected static $next_update_id;
    protected static $non_config = [ 'workers', 'api', 'non_config', 'next_update_id', 'handlers', 'trackers' ];

    public static \TelegramBot\Api\BotApi $api;
    
    static public function init() {

        self::setupProperties();
        self::setupHandlers();
        self::setupTrackers();

        \losthost\DB\DB::connect(self::$db_host, self::$db_user, self::$db_pass, self::$db_name, self::$db_prefix);
        \losthost\telle\PendingUpdate::initDataStructure();
        
        self::$api = new \TelegramBot\Api\BotApi(self::$token); 
        self::$api->setCurlOption(CURLOPT_CAINFO, self::$cacert);
    }

    static protected function setupProperties() {
        global $config;
        
        foreach ($config as $key => $value) {
            if (array_search($key, self::$non_config)) {
                throw new \Exception("Can't change \$$key via global \$config.");
            }
            
            if (property_exists('\losthost\telle\Bot', $key)) {
                self::$$key = $value;
            }
        }
    }
    
    static protected function setupHandlers() {
        global $handlers;
        
        if (isset($handlers) && is_array($handlers)) {
            self::$handlers = $handlers;
        }

        foreach (self::$handlers as $key => $class) {
            
            if (!is_a($class, '\\losthost\\telle\\Handler', true)) {
                throw new \Exception('$handlers must ba an array of \\losthost\\telle\\Handler descendants.');
            }
            self::$handlers[$key] = new $class();
        }
    }
    
    static protected function setupTrackers() {
        global $trackers;
        
        if (isset($trackers) && is_array($trackers)) {
            self::$trackers = $trackers;
        }
        
        foreach (self::$trackers as $tracker => $data ) {
            if (!is_a($tracker, '\\losthost\\DB\\DBTracker', true)) {
                throw new \Exception('$trackers must ba an array of \\losthost\\DB\\DBTracker descendants.');
            }
            \losthost\DB\DB::addTracker($data['events'], $data['objects'], new $tracker());
        }
    }

    static public function run() {
        self::init();
        if (php_sapi_name() == 'cli') {
            self::standalone();
        } else {
            self::handle();
        }
    }
    
    static protected function handle() {
        $data = \TelegramBot\Api\BotApi::jsonValidate(self::getRawBody(), true);
        $updates = \TelegramBot\Api\Types\ArrayOfUpdates::fromResponse($data);
        
        foreach ($updates as $update) 
        {
            self::processHandlers($update);
        }
    }
    
    static public function processHandlers(\TelegramBot\Api\Types\Update &$update, array|null $handlers=null) {
        
        $processed = false;
        if ($handlers === null) {
            $handlers = self::$handlers;
        }
        
        try {
            foreach ($handlers as $handler) {
                
                $handler->initHandler();
                if ((!$processed || $handler->isFinal()) && $handler->checkUpdate($update)) {
                    $processed = $handler->handleUpdate($update);
                }
            }
        } catch (\Exception $e) {
            error_log("Got Exception with code ". $e->getCode(). " while processing handler ". get_class($handler));
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            throw $e;
        }
    }

    static protected function getRawBody() {
        return file_get_contents('php://input');
    }
    
    static protected function standalone() {
        
        if (self::$workers_count <= 1) {
            self::selfProcessing();
        } else {
            self::workersProcessing();
        }
        
        throw new \Exception("Standalone process finished unexpectedly.");
    }

    static protected function getUpdates() {
        while (1) {
            $updates = self::tryGetUpdates();
            if ($updates) {
                return $updates;
            }
        }
    }

    static protected function tryGetUpdates() {
        try {
            $updates = Bot::$api->getUpdates(self::$next_update_id->value, 100, self::$get_updates_timeout);
            if ($updates) {
                return $updates;
            }
        } catch (\Exception $e) {
            if ($e->getCode() != 28) {
                error_log('Exception: '. $e->getCode(). ' - '. $e->getMessage());
            }
        }
        return null;
    }

    static protected function processUpdates($updates) {
        foreach ($updates as $update) {
            self::processHandlers($update);
        }
        
        self::$next_update_id->value = $update->getUpdateId() + 1;
        self::$next_update_id->write();
    }

    static protected function selfProcessing() {
        
        self::$next_update_id = new BotParam('next_update_id', 0);
        while (1) {
            $updates = self::getUpdates();
            self::processUpdates($updates);
        }
    }
    
    static protected function workersProcessing() {
        self::startWorkers();
        self::$next_update_id = new BotParam('next_update_id', 0);
        
        while (1) {
            $updates = self::getUpdates();
            self::dispatchUpdates($updates);
        }
    }

    static function dispatchUpdates($updates=[]) {
        
        self::mergeUnprocessed($updates);
        $free_workers = self::getFreeWorkers();
        
        foreach ($updates as $update) {
            $worker = array_shift($free_workers);
            if ($worker === null) {
                error_log('Waiting for free workers...');
                sleep(1);
                $free_workers = self::getFreeWorkers();
                continue;
            }
            
            new PendingUpdate($update, $worker, self::$max_processing_time);
            self::$workers[$worker]->send($update->getUpdateId());            
        }
        self::$next_update_id->value = $update->getUpdateId() + 1;
        self::$next_update_id->write();
    }
    
    static protected function mergeUnprocessed(&$updates) {

        $pending_updates = new \losthost\DB\DBView(self::SQL_GET_UNPROCESSED_UPDATES, time());
        
        while ($pending_updates->next()) {
            $pending_update = new PendingUpdate($pending_updates->id);
            array_unshift($updates, $pending_update->data);
            $pending_update->delete();
        }
    }

    static protected function getFreeWorkers() {

        $active = new \losthost\DB\DBView(self::SQL_GET_ACTIVE_WORKERS, time());
        $free_workers = array_keys(self::$workers);
        
        while ($active->next()) {
            $index = array_search($active->worker, $free_workers);
            if ($index !== false) {
                unset($free_workers[$index]);
            }
        }
        
        return $free_workers;
    }

    static protected function startWorkers() {
        
        for ($index = 0; $index < self::$workers_count; $index++) {
            
            $wh = new \losthost\telle\WorkerHandle(self::$worker_start_cmd. " $index", $index);
            $wh->run();
            
            self::$workers[$index] = $wh;
        }
    }

    static public function getCaInfo() {
        return self::$cacert;
    }

    const SQL_GET_ACTIVE_WORKERS = <<<END
            SELECT 
                worker
            FROM 
                [pending_updates]
            WHERE 
                locked_till > ?
            END;
    
    const SQL_GET_UNPROCESSED_UPDATES = <<<END
            SELECT id
            FROM [pending_updates]
            WHERE locked_till < ?
            ORDER BY id DESC
            END;
    
    
}
