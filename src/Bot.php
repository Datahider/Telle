<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle;

require_once 'globals.php';

/**
 * Description of Bot
 *
 * @author drweb
 */
class Bot {

    public static \TelegramBot\Api\BotApi $api;

    const UT_CALLBACK_QUERY         = 'callback_query';
    const UT_CHANNEL_POST           = 'channel_post';
    const UT_CHOSEN_INLINE_RELULT   = 'chosen_inline_result';
    const UT_EDITED_CHANNEL_POST    = 'edited_channel_post';
    const UT_EDITED_MESSAGE         = 'edited_message';
    const UT_INLINE_QUERY           = 'inline_query';
    const UT_MESSAGE                = 'message';
    const UT_POLL                   = 'poll';
    const UT_POLL_ANSWER            = 'poll_answer';
    const UT_PRE_CHECKOUT_QUERY     = 'pre_checkout_query';
    const UT_SHIPPING_QUERY         = 'shipping_query';
    const UT_MY_CHAT_MEMBER         = 'my_chat_member';
    const UT_CHAT_MEMBER            = 'chat_member';
    const UT_CHAT_JOIN_REQUEST      = 'chat_join_request';

    const BG_STARTER_WINDOWS        = 'start /b php '. __DIR__. '/starter.php %s %s';
    const BG_STARTER_UNIX           = 'php '. __DIR__. '/starter.php %s %s >/dev/null 2>&1 &';

    protected static $handlers      = [];  
    protected static $workers       = [];
    protected static $param_cache   = [];

    protected static $dbbp_next_update_id;
    protected static $dbbp_bot_alive;
    
    protected static $is_initialized = false;
    
    /**
     * Setups Bot. Must be called before run()
     */
    static public function setup() {
        if (!file_exists('etc/bot_config.php')) {
            throw new \Exception(<<<END
                Config file etc/bot_config.php is not found.
                The file must contain:
                    \$token      = 'The_bot:token_received_from_BotFather';
                    \$ca_cert    = 'Path to cacert.pem';
                    \$db_host    = 'your.database.host';
                    \$db_user    = 'db_username';
                    \$db_pass    = 'Db-PAssWorD';
                    \$db_name    = 'database_name';
                    \$db_prefix  = 'table_prefix_';
                    
                END);
        }
        require 'etc/bot_config.php';
        self::setupApi($token, $ca_cert);
        self::setupDB($db_host, $db_user, $db_pass, $db_name, $db_prefix);
        self::$is_initialized = true;
    }
    
    static protected function setupApi($token,  $ca_cert) {
        self::$api = new \TelegramBot\Api\BotApi($token); 
        self::$api->setCurlOption(CURLOPT_CAINFO, $ca_cert);
    }
    
    static protected function setupDB($db_host, $db_user, $db_pass, $db_name, $db_prefix) {
        \losthost\DB\DB::connect($db_host, $db_user, $db_pass, $db_name, $db_prefix);
    }

    /**
     * Adds a handler to Bot's array of handlers
     * Each handler will be called depending on update type one by one
     * until a handle(...) function return true.
     * After that only final handlers (which ::isFinal() returns true) will be called
     * 
     * @param string $handler_class_name
     */
    static public function addHandler(string $handler_class_name) {
        if (is_a($handler_class_name, abst\AbstractHandlerCallback::class, true)) {
            self::$handlers[self::UT_CALLBACK_QUERY][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChannelPost::class, true)) {
            self::$handlers[self::UT_CHANNEL_POST][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChatJoinRequest::class, true)) {
            self::$handlers[self::UT_CHAT_JOIN_REQUEST][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChatMember::class, true)) {
            self::$handlers[self::UT_CHAT_MEMBER][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChosenInlineResult::class, true)) {
            self::$handlers[self::UT_CHOSEN_INLINE_RELULT][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerEditedChannelPost::class, true)) {
            self::$handlers[self::UT_EDITED_CHANNEL_POST][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerEditedMessage::class, true)) {
            self::$handlers[self::UT_EDITED_MESSAGE][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerInlineQuery::class, true)) {
            self::$handlers[self::UT_INLINE_QUERY][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerMessage::class, true)) {
            self::$handlers[self::UT_MESSAGE][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerMyChatMember::class, true)) {
            self::$handlers[self::UT_MY_CHAT_MEMBER][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerPoll::class, true)) {
            self::$handlers[self::UT_POLL][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerPollAnswer::class, true)) {
            self::$handlers[self::UT_POLL_ANSWER][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerPreCheckoutQuery::class, true)) {
            self::$handlers[self::UT_PRE_CHECKOUT_QUERY][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerShippingQuery::class, true)) {
            self::$handlers[self::UT_SHIPPING_QUERY][] = $handler_class_name;
        }
    }

    static public function run() {
        if (!self::$is_initialized) {
            throw new Exception("Please setup Bot via Bot::setup(...)");
        }
        
        if (php_sapi_name() == 'cli') {
            self::standalone();
        } else {
            self::handle();
        }
    }
    
    /**
     * Handles requests in web-server mode (callback)
     */
    static protected function handle() {
        $data = \TelegramBot\Api\BotApi::jsonValidate(self::getRawBody(), true);
        $updates = \TelegramBot\Api\Types\ArrayOfUpdates::fromResponse($data);
        
        foreach ($updates as $update) 
        {
            self::processHandlers($update);
        }
    }
    
    /**
     * Prepares internal vars and runs processing depending on workers_count
     * @throws \Exception if main loop finishes
     */
    static protected function standalone() {
        
        self::$dbbp_next_update_id = new model\DBBotParam('next_update_id', 0);
        self::$dbbp_bot_alive = new model\DBBotParam('bot_alive', time());
        
        self::truncatePending(new model\DBBotParam('truncate_updates_on_startup', ''));
        
        if ( self::param('workers_count', 1) <= 1) {
            self::selfProcessing();
        } else {
            self::backgroundProcessing();
        }
        
        throw new \Exception("Standalone process finished unexpectedly.");
    }

    static function getUpdateData(\TelegramBot\Api\Types\Update &$update) {
        switch (Env::$update_type) {
            case self::UT_CALLBACK_QUERY:
                return $update->getCallbackQuery();
            case self::UT_CHANNEL_POST:
                return $update->getChannelPost();
            case self::UT_CHAT_JOIN_REQUEST:
                return $update->getChatJoinRequest();
            case self::UT_CHAT_MEMBER:
                return $update->getChatMember();
            case self::UT_CHOSEN_INLINE_RELULT:
                return $update->getChosenInlineResult();
            case self::UT_EDITED_CHANNEL_POST:
                return $update->getEditedChannelPost();
            case self::UT_EDITED_MESSAGE:
                return $update->getEditedMessage();
            case self::UT_INLINE_QUERY:
                return $update->getInlineQuery();
            case self::UT_MESSAGE:
                return $update->getMessage();
            case self::UT_MY_CHAT_MEMBER:
                return $update->getMyChatMember();
            case self::UT_POLL:
                return $update->getPoll();
            case self::UT_POLL_ANSWER:
                return $update->getPollAnswer();
            case self::UT_PRE_CHECKOUT_QUERY:
                return $update->getPreCheckoutQuery();
            case self::UT_SHIPPING_QUERY:
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
            $handlers = isset(self::$handlers[Env::$update_type]) ? self::$handlers[Env::$update_type] : [];
        }
        
        $data = self::getUpdateData($update);
            
        try {
            $processed = self::processPriorityHandler($data);
            foreach ($handlers as $handler_class_name) {
                
                $handler = new $handler_class_name();
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
                $updates = Bot::$api->getUpdates(self::$dbbp_next_update_id->value, 100, 0);
                if (!$updates) {
                    break;
                }
                $last_update = array_pop($updates);
                self::$dbbp_next_update_id->value = $last_update->getUpdateId() + 1;
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
        
        $value = (new model\DBBotParam($name, $default))->value;
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
        self::$dbbp_bot_alive->value = time();
        if (!self::isAlive('cron', self::param('cron_alive_timeout', 60))) {
            self::startCron();
        }
        
        try {
            $updates = Bot::$api->getUpdates(self::$dbbp_next_update_id->value, 100, self::param('get_updates_timeout', 10));
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
        
        self::$dbbp_next_update_id->value = $update->getUpdateId() + 1;
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
        self::$dbbp_next_update_id->value = $update->getUpdateId() + 1;
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

    /**
     * Starts background execution of an AbstractBackgroundProcess descendant
     * @param string $class - A class name
     * @param string $param - A parameter to pass to the class constructor
     * @param string $mode  - mode of the opening handle ('r' or 'w' - the default)
     * @return resource
     */
    static public function startClass(string $class, string $param='', string $mode='w') {
        if (preg_match("/^Windows/", php_uname('s'))) {
            $starter = self::BG_STARTER_WINDOWS;
        } else {
            $starter = self::BG_STARTER_UNIX;
        }
        
        $start_cmd = sprintf($starter, $class, $param);
        return popen($start_cmd, $mode);
    }

    static protected function startCron() {
        self::startClass(BGCron::class);
    }

    static protected function startWorkers() {
        
        if (php_uname('s') === 'Windows') {
            $starter = self::BG_STARTER_WINDOWS;
        } else {
            $starter = self::BG_STARTER_UNIX;
        }

        $workers_count = self::param('workers_count', 1);
        for ($index = 0; $index < $workers_count; $index++) {
            
            $worker_start_cmd = sprintf($starter, BGWorker::class, $index);
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
