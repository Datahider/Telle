<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
namespace losthost\telle;
use Exception;
use losthost\telle\model\DBPendingUpdate;
use losthost\telle\model\DBBotParam;
use losthost\BackgroundProcess\BackgroundProcess;
use losthost\DB\DBValue;

/**
 * Description of Bot
 *
 * @author drweb
 */
class Bot {

    public static ExtendedApi $api;

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
    const UT_MESSAGE_REACTION       = 'message_reaction';
    const UT_MESSAGE_REACTION_COUNT = 'message_reaction_count';
    const UT_COMMAND                = 'command';

    const BG_STARTER_WINDOWS        = 'start /b php "'. __DIR__. '/starter.php" %s %s';
    const BG_STARTER_UNIX           = 'php "'. __DIR__. '/starter.php" %s %s >/dev/null 2>&1';

    protected static array $handlers      = [];
    
    /** @var BackgroundProcess[] $workers */
    protected static array $workers = [];
    protected static $param_cache   = [];
    protected static $cron;  // Нужно для предотвращения тормозов, возникающих при отбрасывании хендлера открытого процесса
    protected static $allowed_updates;

    protected static $dbbp_next_update_id;
    protected static $dbbp_bot_alive;
    
    protected static $is_initialized = false;
    
    protected static $me;
    
    public static ?model\DBUser $user;
    public static ?model\DBChat $chat;
    public static ?int $message_thread_id;
    public static model\DBSession $session;
    public static string $language_code;
    public static string $update_type;
    
    public static string $working_thread;

        /**
     * Setups Bot. Must be called before run()
     */
    static public function setup(string $working_thread = 'self') {
        
        Bot::$working_thread = $working_thread;
        
        if (!file_exists('etc/bot_config.php')) {
            static::throwConfigException('Config file etc/bot_config.php is not found.');
        }
        
        require 'etc/bot_config.php';
        if (empty($token) || empty($timezone) || empty($db_host) || empty($db_user) || empty($db_pass) 
                || empty($db_name) || preg_match("/^Windows/", php_uname('s')) && empty($ca_cert)) {
            if (empty($token) || empty($timezone) || empty($db_host) || empty($db_user) || empty($db_pass) || empty($db_name)) {
                static::throwConfigException('Config file etc/bot_config.php contains incomplete data.');
            }

            if (empty($ca_cert)) {
                $ca_cert = __DIR__. "/cacert.pem";
            }
            
            if (empty($alt_server)) {
                $alt_server = false;
            }
        }
        
        if (is_array($allowed_updates)) {
            static::$allowed_updates = $allowed_updates;
        } elseif ($allowed_updates == 'all') {
            static::$allowed_updates = [
                "message",
                "edited_message",
                "channel_post",
                "edited_channel_post",
                "business_connection",
                "business_message",
                "edited_business_message",
                "deleted_business_messages",
                "message_reaction",
                "message_reaction_count",
                "inline_query",
                "chosen_inline_result",
                "callback_query",
                "shipping_query",
                "pre_checkout_query",
                "purchased_paid_media",
                "poll",
                "poll_answer",
                "my_chat_member",
                "chat_member",
                "chat_join_request",
                "chat_boost",
                "removed_chat_boost",
            ];
        } else {
            static::$allowed_updates = [];
        }
        
        static::setupApi($token, $ca_cert, $alt_server);
        static::setupDB($db_host, $db_user, $db_pass, $db_name, $db_prefix);
        date_default_timezone_set($timezone);

        $bot_username = new DBBotParam('bot_username');
        $bot_userid = new DBBotParam('bot_userid');
        $data = static::getMe();
        $bot_username->value = $data->getUsername();
        $bot_userid->value = $data->getId();

        
        static::setupLogic();
        static::$is_initialized = true;
    }
    
    static public function getMe() {
        if (!isset(static::$me)) {
            static::$me = static::$api->getMe(); 
        }
        return static::$me;
    }
    
    static protected function throwConfigException($text) {
        throw new \Exception(<<<END
            $text
            The file must contain:
                \$token      = 'The_bot:token_received_from_BotFather';
                \$ca_cert    = 'Path to cacert.pem'; // Can be ommited for *nix systems
                \$alt_server = false; // set to true to use local telegram-bot-api server or use 'http://server.addr'
                \$allowed_updates = 'standard'; // Use 'standard', 'all' or array of strings
                \$timezone   = 'Default/Timezone';

                \$db_host    = 'your.database.host';
                \$db_user    = 'db_username';
                \$db_pass    = 'Db-PAssWorD';
                \$db_name    = 'database_name';
                \$db_prefix  = 'table_prefix_';

            END);
    }

    static protected function setupApi($token,  $ca_cert, $alt_server) {
        if ($alt_server === false) {
            static::$api = new ExtendedApi($token); 
            static::$api->setCurlOption(CURLOPT_CAINFO, $ca_cert);
        } elseif($alt_server === true) {
            static::$api = new ExtendedApi($token, null, 'http://localhost/bot'. $token);
        } else {
            static::$api = new ExtendedApi($token, null, $alt_server. '/bot'. $token);
        }
    }
    
    static protected function setupDB($db_host, $db_user, $db_pass, $db_name, $db_prefix) {
        \losthost\DB\DB::connect($db_host, $db_user, $db_pass, $db_name, $db_prefix);
        model\DBPendingUpdate::initDataStructure();
        model\DBPendingJob::initDataStructure();
        model\DBCronEntry::initDataStructure();
        model\DBUser::initDataStructure();
        model\DBBotParam::initDataStructure();
        model\DBChat::initDataStructure();
        model\DBSession::initDataStructure();
    }

    static protected function setupLogic() {
        include 'etc/logic.php';
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
        if (is_a($handler_class_name, abst\AbstractHandlerCommand::class, true)) {
            self::$handlers[self::UT_COMMAND][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerCallback::class, true)) {
            self::$handlers[self::UT_CALLBACK_QUERY][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChannelPost::class, true)) {
            static::$handlers[static::UT_CHANNEL_POST][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChatJoinRequest::class, true)) {
            static::$handlers[static::UT_CHAT_JOIN_REQUEST][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChatMember::class, true)) {
            static::$handlers[static::UT_CHAT_MEMBER][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerChosenInlineResult::class, true)) {
            static::$handlers[static::UT_CHOSEN_INLINE_RELULT][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerEditedChannelPost::class, true)) {
            static::$handlers[static::UT_EDITED_CHANNEL_POST][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerEditedMessage::class, true)) {
            static::$handlers[static::UT_EDITED_MESSAGE][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerInlineQuery::class, true)) {
            static::$handlers[static::UT_INLINE_QUERY][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerMessage::class, true)) {
            static::$handlers[static::UT_MESSAGE][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerMyChatMember::class, true)) {
            static::$handlers[static::UT_MY_CHAT_MEMBER][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerPoll::class, true)) {
            static::$handlers[static::UT_POLL][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerPollAnswer::class, true)) {
            static::$handlers[static::UT_POLL_ANSWER][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerPreCheckoutQuery::class, true)) {
            static::$handlers[static::UT_PRE_CHECKOUT_QUERY][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerShippingQuery::class, true)) {
            self::$handlers[self::UT_SHIPPING_QUERY][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerMessageReaction::class, true)) {
            self::$handlers[self::UT_MESSAGE_REACTION][] = $handler_class_name;
        } elseif (is_a($handler_class_name, abst\AbstractHandlerMessageReactionCount::class, true)) {
            self::$handlers[self::UT_MESSAGE_REACTION_COUNT][] = $handler_class_name;
        } else {
            throw new \Exception('The handler must be a descendant of one of abstract handlers.');
        }
    }

    static public function runAt(\DateTime|\DateTimeImmutable|string $start_time, string $job_class, ?string $job_args='', bool $in_background=false) {
        if (is_string($start_time)) {
            new model\DBCronEntry($start_time, $in_background, $job_class, $job_args);
        } else {
            new model\DBPendingJob($start_time, $in_background, $job_class, $job_args);
        }
    }
    
    static public function run() {
        if (!static::$is_initialized) {
            throw new Exception("Please setup Bot via Bot::setup(...)");
        }
        
        if (php_sapi_name() == 'cli') {
            static::standalone();
        } else {
            static::handle();
        }
    }
    
    /**
     * Handles requests in web-server mode (callback)
     */
    static protected function handle() {
        $data = ExtendedApi::jsonValidate(static::getRawBody(), true);
        $update = \TelegramBot\Api\Types\Update::fromResponse($data);
        static::processHandlers($update);
        return;
    }
    
    /**
     * Prepares internal vars and runs processing depending on workers_count
     * @throws \Exception if main loop finishes
     */
    static protected function standalone() {
        
        static::$dbbp_next_update_id = new model\DBBotParam('next_update_id', 0);
        static::$dbbp_bot_alive = new model\DBBotParam('bot_alive', time());
        
        static::truncatePending(new model\DBBotParam('truncate_updates_on_startup', ''));
        
        static::backgroundProcessing();
        
        throw new \Exception("Standalone process finished unexpectedly.");
    }

    static function getUpdateData(\TelegramBot\Api\Types\Update &$update) {
        switch (static::$update_type) {
            case static::UT_CALLBACK_QUERY:
                return $update->getCallbackQuery();
            case static::UT_CHANNEL_POST:
                return $update->getChannelPost();
            case static::UT_CHAT_JOIN_REQUEST:
                return $update->getChatJoinRequest();
            case static::UT_CHAT_MEMBER:
                return $update->getChatMember();
            case static::UT_CHOSEN_INLINE_RELULT:
                return $update->getChosenInlineResult();
            case static::UT_EDITED_CHANNEL_POST:
                return $update->getEditedChannelPost();
            case static::UT_EDITED_MESSAGE:
                return $update->getEditedMessage();
            case static::UT_INLINE_QUERY:
                return $update->getInlineQuery();
            case static::UT_MESSAGE:
                return $update->getMessage();
            case static::UT_MY_CHAT_MEMBER:
                return $update->getMyChatMember();
            case static::UT_POLL:
                return $update->getPoll();
            case static::UT_POLL_ANSWER:
                return $update->getPollAnswer();
            case static::UT_PRE_CHECKOUT_QUERY:
                return $update->getPreCheckoutQuery();
            case static::UT_SHIPPING_QUERY:
                return $update->getShippingQuery();
            case static::UT_MESSAGE_REACTION:
                return $update->getMessageReaction();
            case static::UT_MESSAGE_REACTION_COUNT:
                return $update->getMessageReactionCount();
            default:
                throw new Exception("Unknown update type.");
        }
    }

    static public function logException(\Exception $ex, string $comment='',) {
        
        $date = date_create_immutable()->format('Y-m-d H:i:s.u');
        
        $thread = Bot::$working_thread;
        ("($thread)$date - ". $ex->getMessage(). '('. $ex->getCode(). ')');
        error_log("($thread)$date - ". $ex->getMessage());
        error_log("($thread)$date - ". $ex->getTraceAsString());

        if ($comment) {
            error_log("$date - $comment");
        }
    }
    
    static public function logComment(string $comment, string $file='', ?int $line=null) {
        $date = date_create_immutable()->format('Y-m-d H:i:s.u');
        
        if ($file) {
            $comment .= " in file $file ($line)";
        }
        $thread = Bot::$working_thread;
        error_log("($thread)$date - $comment");
    }

    static protected function processPriorityHandler($data) : bool {
        
        $priority_handler_class = self::$session->priority_handler;
        if (!$priority_handler_class) {
            return false; // There is no priority handler set in this session
        }

        if (!is_a($data, \TelegramBot\Api\Types\Message::class)) {
            self::logComment("Priority handler $priority_handler_class ignored as it is for message upadte types only", __FILE__, __LINE__);
            return false; // Priority handlers are for message update only
        }
        
        $priority_handler = new ($priority_handler_class)();
        $priority_handler->initHandler();

        try {
            if ($priority_handler->checkUpdate($data)) {
                return $priority_handler->handleUpdate($data);
            }
        } catch (\Exception $ex) {
            $priority_handler->unsetPriority();
            self::logException($ex, 'Priority handler was reset due to an exception while handling.');
            return false;
        }
        return false;
    }
    
    static protected function processCommandHandlers(\TelegramBot\Api\Types\Message &$message) : bool {

        if (empty(self::$handlers[self::UT_COMMAND])) {
            return false;
        }
        
        foreach (self::$handlers[self::UT_COMMAND] as $handler_class_name) {
            
            try {
                $handler = new $handler_class_name();
                $handler->initHandler();

                if (!$handler->checkUpdate($message)) {
                    continue;
                }

                $processed = $handler->handleUpdate($message);
                if ($processed) {
                    return true;
                }
            } catch (\Exception $exc) {
                self::logException($exc, "Got Exception while processing handler $handler_class_name");
            }

        }
        return false;
    }
    
    static public function processHandlers(\TelegramBot\Api\Types\Update &$update, array|null $handlers=null) {
        
        if ($handlers === null) {
            static::load($update);
            $handlers = isset(static::$handlers[static::$update_type]) ? static::$handlers[static::$update_type] : [];
        }
        
        $data = static::getUpdateData($update);
            
        try {
            $processed = self::processPriorityHandler($data);
            if (!$processed
                    && self::$update_type == self::UT_MESSAGE
                    && $data->getText()
                    && preg_match("/^\/([a-zA-Z0-9_]+)\s*(.*)$/s", $data->getText())) {
                $processed = self::processCommandHandlers($data);    
            }
            foreach ($handlers as $handler_class_name) {
                
                $handler = new $handler_class_name();
                $handler->initHandler();
                if ((!$processed || $handler_class_name::IS_FINAL) && $handler->checkUpdate($data)) {
                    $processed = $handler->handleUpdate($data);
                }
            }
        } catch (\Exception $e) {
            self::logException($e, "Got Exception while processing handler $handler_class_name.");
        }
    }

    static protected function getRawBody() {
        return file_get_contents('php://input');
    }
    
    static protected function truncatePending($truncate_updates_on_startup) {
        
        if ($truncate_updates_on_startup->value) {
            DBPendingUpdate::truncate();
            while (1) {
                $updates = static::$api->getUpdates(static::$dbbp_next_update_id->value, 100, 0, static::$allowed_updates);
                if (!$updates) {
                    break;
                }
                $last_update = array_pop($updates);
                static::$dbbp_next_update_id->value = $last_update->getUpdateId() + 1;
            }
        }
        
        $truncate_updates_on_startup->value = '';
    }

    static public function isAlive($who, $timeout=60) {
        $name = $who. "_alive";
        $last_alive = static::param($name, 0);
        $now = time();

        if ($last_alive < $now-$timeout) {
            return false;
        }
        return true;
    }

    static public function param($name, $default) {
        if (isset(static::$param_cache[$name])) {
            $param_data = static::$param_cache[$name];
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
            $expires = time() + static::param('param_cache_time', 600);
        }
        
        static::$param_cache[$name] = compact('value', 'expires');
        return $value;
    }

    static protected function getUpdates() {
        
        while (1) {
            $updates = static::tryGetUpdates();
            if ($updates) {
                return $updates;
            }
        }
    }

    static protected function tryGetUpdates() {
        static::$dbbp_bot_alive->value = time();
        if (!static::isAlive('cron', static::param('cron_alive_timeout', 60))) {
            static::startCron();
        }
        
        try {
            $updates = static::$api->getUpdates(static::$dbbp_next_update_id->value, 100, static::param('get_updates_timeout', 10), static::$allowed_updates);
            if ($updates) {
                return $updates;
            }
        } catch (\Exception $e) {
            if ($e->getCode() != 28) {
                self::logException($e);
            }
        }
        return null;
    }

    static protected function processUpdates($updates) {
        foreach ($updates as $update) {
            static::processHandlers($update);
        }
        
        static::$dbbp_next_update_id->value = $update->getUpdateId() + 1;
    }

    static protected function selfProcessing() {
        
        while (1) {
            $updates = static::getUpdates();
            static::processUpdates($updates);
        }
    }
    
    static protected function backgroundProcessing() {
 
        static::checkWorkers();
        
        while (1) {
            $updates = static::getUpdates();
            static::enqueueUpdates($updates);
            static::checkWorkers();
        }
    }

    static protected function enqueueUpdates(array $updates) {
        foreach ($updates as $update) {
            DBPendingUpdate::add($update);
            static::$dbbp_next_update_id->value = $update->getUpdateId() + 1;
        }
    }
    
    static function dispatchUpdates($updates=[]) {
        
        $free_workers = static::getFreeWorkers();
        
        foreach ($updates as $update) {
            while ( null === $worker = array_shift($free_workers)) {
                self::logComment('Waiting for free workers...', __FILE__, __LINE__);
                sleep(1);
                $free_workers = static::getFreeWorkers();
            }
            
            try {
                new DBPendingUpdate($update, $worker, static::param('max_processing_time', 15));
                static::$workers[$worker]->send($update->getUpdateId());            
            } catch (\Exception $ex) {
                // Не смогли записать, уже обрабатывается с прошлого падения
                // Ну и хер с ним...
                Bot::logException($ex);
            }

        }
        static::$dbbp_next_update_id->value = $update->getUpdateId() + 1;
    }
    
    static protected function getPendingUpdates() {

        $updates = [];
        $pending_updates = new \losthost\DB\DBView(static::SQL_GET_UNPROCESSED_UPDATES, time());
        
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

        $active = new \losthost\DB\DBView(static::SQL_GET_ACTIVE_WORKERS, time());
        $free_workers = array_keys(static::$workers);
        
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
        $start_cmd = static::getStartCmd($class, $param);
        return popen($start_cmd, $mode);
    }

    static protected function getStartCmd(string $class, string $param) {
        
        if (preg_match("/^Windows/", php_uname('s'))) {
            $starter = static::BG_STARTER_WINDOWS;
        } else {
            $starter = static::BG_STARTER_UNIX;
        }
        
        $start_cmd = sprintf($starter, escapeshellarg($class), escapeshellarg($param));
        $start_cmd = preg_replace("/^php /", PHP_BINARY. ' ', $start_cmd);
        return $start_cmd;
    }

    static protected function startCron() {
        Bot::$cron = static::startClass(BGCron::class);
    }

    static protected function checkWorkers() {
        /** @var BackgroundProcess $worker */
        $lock = new DBValue(Worker::LOCK_GET, [Worker::LOCK_IDLE, 0]);
        if ($lock->locked > 0) {
            $worker_template = file_get_contents(__DIR__. '/worker-template.php');
            if ($worker_template === false) {
                throw new \RuntimeException("Can't open worker-template.php file");
            }
            BackgroundProcess::create($worker_template)
                    ->run(uniqid('w'));
            new DBValue(Worker::LOCK_RELEASE, Worker::LOCK_IDLE);
        }
    }
    
    static public function getCaInfo() {
        return static::$cacert;
    }

    /**
     * Next function are moved here from former Env class
     */
    
    static protected function initLast() {
        if ( static::$user !== null && static::$user->language_code !== null ) {
            static::$language_code = static::$user->language_code;
        } else {
            static::$language_code = 'default';
        }
        static::$session = new model\DBSession(static::$user, static::$chat, static::$message_thread_id);
    }

    static function load(\TelegramBot\Api\Types\Update &$update) : bool {
        
        if ($callback_query = $update->getCallbackQuery()) {
            static::initByCallbackQuery($callback_query);
        } elseif ($chanel_post = $update->getChannelPost()) {
            static::initByChannelPost($chanel_post);
        } elseif ($chosen_inline_result = $update->getChosenInlineResult()) {
            static::initByChosenInlineResult($chosen_inline_result);
        } elseif ($edited_channel_post = $update->getEditedChannelPost()) {
            static::initByEditedChannelPost($edited_channel_post);
        } elseif ($edited_message = $update->getEditedMessage()) {
            static::initByEditedMessage($edited_message);
        } elseif ($inline_query = $update->getInlineQuery()) {
            static::initByInlineQuery($inline_query);
        } elseif ($message = $update->getMessage()) {
            static::initByMessage($message);
        } elseif ($poll = $update->getPoll()) {
            static::initByPoll($poll);
        } elseif ($poll_answer = $update->getPollAnswer()) {
            static::initByPollAnswer($poll_answer);
        } elseif ($pre_checkout_query = $update->getPreCheckoutQuery()) {
            static::initByPreCheckoutQuery($pre_checkout_query);
        } elseif ($shipping_query = $update->getShippingQuery()) {
            static::initByShippingQuery($shipping_query);
        } elseif ($my_chat_member = $update->getMyChatMember()) {
            static::initByMyChatMember($my_chat_member);
        } elseif ($chat_member = $update->getChatMember()) {
            static::initByChatMember($chat_member);
        } elseif ($chat_join_request = $update->getChatJoinRequest()) {
            static::initByMyChatMember($chat_join_request);
        } elseif ($message_reaction = $update->getMessageReaction()) {
            static::initByMessageReaction($message_reaction);
        } elseif ($message_reaction_count = $update->getMessageReactionCount()) {
            static::initByMessageReactionCount($message_reaction_count);
        } else {
            throw new \Exception("Can't load Env.");
        }
        
        static::initLast();
        
        return false;
    }
    
    static protected function initByCallbackQuery(\TelegramBot\Api\Types\CallbackQuery &$callback_query) {
        static::$update_type = static::UT_CALLBACK_QUERY;
        $from = $callback_query->getFrom();
        static::$user = new model\DBUser($from);
        
        $chat = $callback_query->getMessage()->getChat();
        static::$chat = new model\DBChat($chat);
        
        static::$message_thread_id = $callback_query->getMessage()->getMessageThreadId();
    }
    
    static protected function initByChannelPost(\TelegramBot\Api\Types\Message &$channel_post) {
        static::initByMessage($channel_post);
        static::$update_type = static::UT_CHANNEL_POST;
    }
    
    static protected function initByChosenInlineResult(\TelegramBot\Api\Types\Inline\ChosenInlineResult &$chosen_inline_result) {
        static::$update_type = static::UT_CHOSEN_INLINE_RELULT;
        $from = $chosen_inline_result->getFrom();
        static::$user = new model\DBUser($from);
        static::$chat = null;
        static::$message_thread_id = null;
    }
    
    static protected function initByEditedChannelPost(\TelegramBot\Api\Types\Message &$edited_channel_post) {
        static::initByMessage($edited_channel_post);
        static::$update_type = static::UT_EDITED_CHANNEL_POST;
    }
    
    static protected function initByEditedMessage(\TelegramBot\Api\Types\Message &$edited_message) {
        static::initByMessage($edited_message);
        static::$update_type = static::UT_EDITED_MESSAGE;
    }
    
    static protected function initByInlineQuery(\TelegramBot\Api\Types\Inline\InlineQuery &$inline_query) {
        static::$update_type = static::UT_INLINE_QUERY;
        $from = $inline_query->getFrom();
        static::$user = new model\DBUser($from);
        static::$chat = null;
        static::$message_thread_id = null;
    }
    
    static protected function initByMessage(\TelegramBot\Api\Types\Message &$message) {
        static::$update_type = static::UT_MESSAGE;
        $from = $message->getFrom();
        if ($from) {
            static::$user = new model\DBUser($from);
        } else {
            static::$user = null;
        }
        
        $chat = $message->getChat();
        static::$chat = new model\DBChat($chat);

        static::$message_thread_id = $message->getMessageThreadId();
    }
    
    static protected function initByPoll(\TelegramBot\Api\Types\Poll &$poll) {
        static::$update_type = static::UT_POLL;
        static::$user = null;
        static::$chat = null;
        static::$message_thread_id = null;
    }
    
    static protected function initByPollAnswer(\TelegramBot\Api\Types\PollAnswer &$poll_answer) {
        static::$update_type = static::UT_POLL_ANSWER;
        $user = $poll_answer->getUser();
        if ($user) {
            static::$user = new model\DBUser($user);
        } else {
            static::$user = null;
        }
        static::$chat = null;
        static::$message_thread_id = null;
    }
    
    static protected function initByPreCheckoutQuery(\TelegramBot\Api\Types\Payments\Query\PreCheckoutQuery &$pre_checkout_query) {
        static::$update_type = static::UT_PRE_CHECKOUT_QUERY;
        $from = $pre_checkout_query->getFrom();
        static::$user = new model\DBUser($from);
        static::$chat = null;
        static::$message_thread_id = null;
    }
    
    static protected function initByShippingQuery(\TelegramBot\Api\Types\Payments\Query\ShippingQuery &$shipping_query) {
        static::$update_type = static::UT_SHIPPING_QUERY;
        $from = $shipping_query->getFrom();
        static::$user = new model\DBUser($from);
        static::$chat = null;
        static::$message_thread_id = null;
    }
    
    static protected function initByMyChatMember(\TelegramBot\Api\Types\ChatMemberUpdated &$chat_member) {
        static::initByChatMember($chat_member);
        static::$update_type = static::UT_MY_CHAT_MEMBER;
    }
    
    static protected function initByChatMember(\TelegramBot\Api\Types\ChatMemberUpdated &$chat_member) {
        static::$update_type = static::UT_CHAT_MEMBER;
        $from = $chat_member->getFrom();
        static::$user = new model\DBUser($from);
        
        $chat = $chat_member->getChat();
        static::$chat = new model\DBChat($chat);

        static::$message_thread_id = null;
    }
    
    static protected function initByChatJoinRequest(\TelegramBot\Api\Types\ChatJoinRequest &$chat_join_request) {
        static::$update_type = static::UT_CHAT_JOIN_REQUEST;
        $from = $chat_join_request->getFrom();
        static::$user = new model\DBUser($from);

        $chat = $chat_join_request->getChat();
        static::$chat = new model\DBChat($chat);
        
        static::$message_thread_id = null;
    }
    
    static protected function initByMessageReaction(\TelegramBot\Api\Types\MessageReactionUpdated &$message_reaction) {
        static::$update_type = static::UT_MESSAGE_REACTION;
        $from = $message_reaction->getUser();
        static::$user = new model\DBUser($from);
        
        $chat = $message_reaction->getChat();
        static::$chat = new model\DBChat($chat);
        
        static::$message_thread_id = null;
    }
    
    static protected function initByMessageReactionCount(\TelegramBot\Api\Types\MessageReactionCountUpdated &$message_reaction_count) {
        static::$update_type = static::UT_MESSAGE_REACTION_COUNT;

        static::$user = null;
        
        $chat = $message_reaction_count->getChat();
        static::$chat = new model\DBChat($chat);
        
        static::$message_thread_id = null;
    }
    
    
    const SQL_GET_ACTIVE_WORKERS = <<<END
            SELECT 
                worker
            FROM 
                [telle_pending_updates]
            WHERE 
                locked_till > ?
            END;
    
    const SQL_GET_UNPROCESSED_UPDATES = <<<END
            SELECT id
            FROM [telle_pending_updates]
            WHERE locked_till < ?
            ORDER BY id ASC
            END;
    
    
}
