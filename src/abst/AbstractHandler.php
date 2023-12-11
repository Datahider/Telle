<?php

namespace losthost\telle\abst;

use losthost\telle\Env;
use losthost\telle\model\DBSession;

/**
 * Description of Handler
 *
 * @author drweb_000
 */
abstract class AbstractHandler {

    protected $check_cache;

    public function __construct() {
        $this->initHandler();
    }
    
    abstract public function isFinal() : bool;
    abstract protected function init() : void;

    public function initHandler() {
        $this->check_cache = null;
        $this->init();
    }
    
    public function checkUpdate(\TelegramBot\Api\BaseType &$data) : bool {

        if ($this->check_cache === null) {
            $this->check_cache = $this->check($data);
        }
        
        return $this->check_cache; 
    }

    public function handleUpdate(\TelegramBot\Api\BaseType &$data) : bool {

        if (!$this->checkUpdate($data)) {
            return false;
        }
        return $this->handle($data);
    }
    
    static public function setPriority(mixed $data) {
        Env::$session->set(DBSession::FIELD_PRIORITY_HANDLER, static::class);
        Env::$session->set('data', $data);
    }
    
    static public function unsetPriority() {
        Env::$session->set(DBSession::FIELD_PRIORITY_HANDLER, null);
        Env::$session->set('data', null);
    }
    
}
