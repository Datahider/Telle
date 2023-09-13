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

    protected static $handlers = [                      // array of Handler descendant names
        '\\losthost\\telle\\LoggerHandler',                 // Logs text messages to error log
        '\\losthost\\telle\\StartCommandHandler',           // Handles /start command
        '\\losthost\\telle\\EchoHandler',                   // Sends echo text back to user
        '\\losthost\\telle\\CallbackHandler',               // Handles callback queries
        '\\losthost\\telle\\FinalHandler',                  // Sends final message
        '\\losthost\\telle\\AnotherFinalHandler',           // This is for non-text updates
    ];  
    /// End of config 
    
    
    protected static $workers = [];
    protected static $next_update_id;
    protected static $non_config = [ 'workers', 'api', 'non_config', 'next_update_id' ];

    public static \TelegramBot\Api\BotApi $api;
    
    static public function init() {

        self::setupProperties();

        \losthost\DB\DB::connect(self::$db_host, self::$db_user, self::$db_pass, self::$db_name, self::$db_prefix);
        
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
        
        static::setupHandlers();
    }
    
    static protected function setupHandlers() {
        
        if (!is_array(self::$handlers)) {
            throw new \Exception("\$config['handlers'] must be an array of \losthost\telle\Handler descendants");
        }

        foreach (self::$handlers as $key => $class) {
            
            if (!is_a($class, '\losthost\telle\Handler', true)) {
                throw new \Exception('$config->handlers must ba an array of Handler descendants.');
            }
            self::$handlers[$key] = new $class();
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
    
    static public function processHandlers(\TelegramBot\Api\Types\Update &$update) {
        
        $last = false;
        
        foreach (self::$handlers as $handler) {
            
            if ((!$last || $handler->isFinal()) && $handler->checkUpdate($update)) {
                $handler->handleUpdate($update);
            }

            if (!$last) {
                $last = $handler->isLast();
            }
        }
    }

    static protected function getRawBody() {
        return file_get_contents('php://input');
    }
    
    static protected function standalone() {
        
        self::startWorkers();
        self::$next_update_id = new BotParam('next_update_id', 0);
        
        self::dispatch();
        
        while (1) {
            self::updatesLoopIteration();
        }
        throw new \Exception("Standalone process finished unexpectedly.");
    }

    static protected function updatesLoopIteration() {
        
        try {
            while (1) {
                $updates = Bot::$api->getUpdates(self::$next_update_id->value, 100, self::$get_updates_timeout);
                if (!$updates) {
                    continue;
                }
                
                self::processUpdates($updates);
            }
        } catch (\TelegramBot\Api\Exception $ex) {
            if ($ex->getCode() != 28) {
                throw $ex;
            }
        }
    }

    static protected function processUpdates(&$updates) {
        
        foreach ($updates as $update) {
            new Update($update);
            self::$next_update_id->value = $update->getUpdateId() + 1;
            self::$next_update_id->write();
        }

        self::dispatch();
    }
    
    static protected function dispatch() {
        $now = time();
        $to_process = new \losthost\DB\DBView(self::SQL_GET_UPDATES_TO_PROCESS, time());
        $free_workers = self::getFreeWorkers($now);

        while ($to_process->next()) {
            $worker = array_shift($free_workers);
            if ($worker === null) {
                sleep(1);
                $free_workers = self::getFreeWorkers($now);
            }

            $update = new Update($to_process->id);
            $update->locked_till = $now + self::$max_processing_time;
            $update->worker = $worker;
            $update->write();
            self::$workers[$worker]->send($to_process->id);
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

    static public function getHandlers() {
        return self::$handlers;
    }
    
    static public function getCaInfo() {
        return self::$cacert;
    }

    const SQL_GET_ACTIVE_WORKERS = <<<END
            SELECT 
                worker
            FROM 
                [updates]
            WHERE 
                worker IS NOT NULL
                AND state <> 255
                AND locked_till > ?
            END;
    
    const SQL_GET_UPDATES_TO_PROCESS = <<<END
            SELECT
                id
            FROM    
                [updates]
            WHERE
                state <> 255
                AND (
                    locked_till IS NULL
                    OR locked_till <= ?
                )
            ORDER BY 
                id
            END;
    
}
