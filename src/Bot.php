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
    protected static string $token = '';                // bot token
    
    protected static string $db_host = 'localhost';     // database host
    protected static string $db_user = '';              // database user
    protected static string $db_pass = '';              // database password
    protected static string $db_name = '';              // database name
    protected static string $db_prefix = 'telle_';      // database table prefix
    
    // path to the cacert file for curl                 //
    protected static string $cacert = __DIR__. '/cacert.pem';

    // command to start worker process in background    //
    protected static string $starter = 'start /b php '. __DIR__. '/starter.php %s %s'; // Windows
    //protected static string $starter = 'php '. __DIR__. '/starter.php %s %s >/dev/null 2>&1 &'; // *nix
    protected static $worker_class = '\\losthost\\telle\\BGWorker';
    protected static $cron_class = '\\losthost\\telle\\BGCron';
    protected static $cron_process;

    protected static $handlers = [                  // array of Handlers. Override by global $trackers 
        
        Env::UT_MESSAGE => [
            '\\losthost\\telle\\HandlerMessageLogger',      // Logs text messages to error log
            '\\losthost\\telle\\HandlerCommandStart',       // Handles /start command
            '\\losthost\\telle\\HandlerEcho',               // Sends echo text back to user
            '\\losthost\\telle\\HandlerFinal',              // Sends final message
        ],
        Env::UT_CALLBACK_QUERY => [
            '\\losthost\\telle\\HandlerCallback',           // Handles callback queries
            '\\losthost\\telle\\HandlerFinalCallback',      // This is for non-text updates
        ],
    ];  
    
    protected static $trackers = [                  // Array of DBTrackers. Override by global $trackers
        \losthost\telle\TrackerLogger::class => [   // Class name as array key
            'events' => [                               // Use \losthost\DB\DBEvent::ALL_EVENTS for all
                \losthost\DB\DBEvent::AFTER_INSERT,
                \losthost\DB\DBEvent::AFTER_UPDATE,
                \losthost\DB\DBEvent::AFTER_DELETE,
            ],
            'objects' => [                              // Use '*' for all
                DBBotParam::class, 
                DBPendingUpdate::class,
            ],
                                                        // Add more if you need
        ]
    ];
    /// End of config 
    
    
    protected static $workers = [];
    protected static $next_update_id;
    protected static $bot_alive;
    protected static $param_cache = [];
    protected static $non_config = [ 'workers', 'api', 'non_config', 'next_update_id', 'handlers', 'trackers', 'param_cache' ];

    public static \TelegramBot\Api\BotApi $api;
    protected static $is_initialized = false;

    static public function init() {
        
        self::setupProperties();
        self::setupHandlers();
        self::setupTrackers();

        \losthost\DB\DB::connect(self::$db_host, self::$db_user, self::$db_pass, self::$db_name, self::$db_prefix);
        
        self::$api = new \TelegramBot\Api\BotApi(self::$token); 
        self::$api->setCurlOption(CURLOPT_CAINFO, self::$cacert);
        
        self::$is_initialized = true;
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

        foreach (self::$handlers as $key => $handlers) {
            foreach ($handlers as $index => $class) {
                if (!is_a($class, '\\losthost\\telle\\AbstractHandler', true)) {
                    throw new \Exception('$handlers must ba an associative array of \\losthost\\telle\\AbstractHandler descendants arrays.');
                }
                self::$handlers[$key][$index] = new $class();
            }
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
        if (!self::$is_initialized) {
            self::init();
        }
        
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
    
    static function getUpdateData(\TelegramBot\Api\Types\Update &$update) {
        switch (Env::$update_type) {
            case Env::UT_CALLBACK_QUERY:
                return $update->getCallbackQuery();
            case Env::UT_CHANNEL_POST:
                return $update->getChannelPost();
            case Env::UT_CHAT_JOIN_REQUEST:
                return $update->getChatJoinRequest();
            case Env::UT_CHAT_MEMBER:
                return $update->getChatMember();
            case Env::UT_CHOSEN_INLINE_RELULT:
                return $update->getChosenInlineResult();
            case Env::UT_EDITED_CHANNEL_POST:
                return $update->getEditedChannelPost();
            case Env::UT_EDITED_MESSAGE:
                return $update->getEditedMessage();
            case Env::UT_INLINE_QUERY:
                return $update->getInlineQuery();
            case Env::UT_MESSAGE:
                return $update->getMessage();
            case Env::UT_MY_CHAT_MEMBER:
                return $update->getMyChatMember();
            case Env::UT_POLL:
                return $update->getPoll();
            case Env::UT_POLL_ANSWER:
                return $update->getPollAnswer();
            case Env::UT_PRE_CHECKOUT_QUERY:
                return $update->getPreCheckoutQuery();
            case Env::UT_SHIPPING_QUERY:
                return $update->getShippingQuery();
            default:
                throw new Exception("Unknown update type.");
        }
    }

    static protected function processPriorityHandler($data) {
        if (Env::$session->priority_handler) {
            $priority_handler = new (Env::$session->priority_handler)();
            $priority_handler->initHandler();
            
            try {
                if ($priority_handler->checkUpdate($data)) {
                    return $priority_handler->handleUpdate($data);
                }
            } catch (\Exception $ex) {
                $priority_handler->unsetPriority();
                return false;
            } catch (\TypeError $ex) {
                $priority_handler->unsetPriority();
                return false;
            }
        }
    }

    static public function processHandlers(\TelegramBot\Api\Types\Update &$update, array|null $handlers=null) {
        
        if ($handlers === null) {
            Env::load($update);
            $handlers = self::$handlers[Env::$update_type];
        }
        
        $data = self::getUpdateData($update);
            
        try {
            $processed = self::processPriorityHandler($data);
            foreach ($handlers as $handler) {
                
                $handler->initHandler();
                if ((!$processed || $handler->isFinal()) && $handler->checkUpdate($data)) {
                    $processed = $handler->handleUpdate($data);
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
    
    static protected function truncatePending($truncate_updates_on_startup) {
        
        if ($truncate_updates_on_startup->value) {
            DBPendingUpdate::truncate();
            while (1) {
                $updates = Bot::$api->getUpdates(self::$next_update_id->value, 100, 0);
                if (!$updates) {
                    break;
                }
                $last_update = array_pop($updates);
                self::$next_update_id->value = $last_update->getUpdateId() + 1;
            }
        }
        
        $truncate_updates_on_startup->value = '';
    }

    static public function isAlive($who, $timeout=60) {
        $name = $who. "_alive";
        $last_alive = self::param($name, 0);
        $now = time();

        if ($last_alive < $now-$timeout) {
            return false;
        }
        return true;
    }

    static public function param($name, $default) {
        if (isset(self::$param_cache[$name])) {
            $param_data = self::$param_cache[$name];
            if ($param_data['expires'] > time()) {
                return $param_data['value'];
            }
        }
        
        $value = (new DBBotParam($name, $default))->value;
        if ($name == 'next_update_id' || preg_match("/_alive$/", $name)) {
            return $value;
        }
        
        if ($name == 'param_cache_time') {
            $expires = time() + $value;
        } else {
            $expires = time() + self::param('param_cache_time', 600);
        }
        
        self::$param_cache[$name] = compact('value', 'expires');
        return $value;
    }

    static protected function standalone() {
        
        self::$next_update_id = new DBBotParam('next_update_id', 0);
        self::$bot_alive = new DBBotParam('bot_alive', time());
        
        self::truncatePending(new DBBotParam('truncate_updates_on_startup', ''));
        
        if ( self::param('workers_count', 1) <= 1) {
            self::selfProcessing();
        } else {
            self::backgroundProcessing();
        }
        
        throw new \Exception("Standalone process finished unexpectedly.");
    }

    static protected function getUpdates() {
        
        $updates = self::getPendingUpdates();
        if ($updates) {
            return $updates;
        }
        
        while (1) {
            $updates = self::tryGetUpdates();
            if ($updates) {
                return $updates;
            }
        }
    }

    static protected function tryGetUpdates() {
        self::$bot_alive->value = time();
        if (!self::isAlive('cron', self::param('cron_alive_timeout', 60))) {
            self::startCron();
        }
        
        try {
            $updates = Bot::$api->getUpdates(self::$next_update_id->value, 100, self::param('get_updates_timeout', 10));
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
    }

    static protected function selfProcessing() {
        
        while (1) {
            $updates = self::getUpdates();
            self::processUpdates($updates);
        }
    }
    
    static protected function backgroundProcessing() {
        self::startWorkers();
        
        while (1) {
            $updates = self::getUpdates();
            self::dispatchUpdates($updates);
        }
    }

    static function dispatchUpdates($updates=[]) {
        
        $free_workers = self::getFreeWorkers();
        
        foreach ($updates as $update) {
            while ( null === $worker = array_shift($free_workers)) {
                error_log('Waiting for free workers...');
                sleep(1);
                $free_workers = self::getFreeWorkers();
            }
            
            new DBPendingUpdate($update, $worker, self::param('max_processing_time', 15));
            self::$workers[$worker]->send($update->getUpdateId());            
        }
        self::$next_update_id->value = $update->getUpdateId() + 1;
    }
    
    static protected function getPendingUpdates() {

        $updates = [];
        $pending_updates = new \losthost\DB\DBView(self::SQL_GET_UNPROCESSED_UPDATES, time());
        
        while ($pending_updates->next()) {
            $pending_update = new DBPendingUpdate($pending_updates->id);
            $updates[] = $pending_update->data;
            $pending_update->delete();
        }
        
        if (count($updates) == 0) {
            return null;
        } else {
            return $updates;
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

    static public function startClass($class, $param='', $mode='w') {
        $start_cmd = sprintf(self::$starter, $class, $param);
        return popen($start_cmd, $mode);
    }

    static protected function startCron() {
        self::$cron_process = self::startClass(self::$cron_class);
    }

    static protected function startWorkers() {
        
        $workers_count = self::param('workers_count', 1);
        for ($index = 0; $index < $workers_count; $index++) {
            
            $worker_start_cmd = sprintf(self::$starter, self::$worker_class, $index);
            $wh = new \losthost\telle\WorkerHandle($worker_start_cmd, $index);
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
            ORDER BY id ASC
            END;
    
    
}
