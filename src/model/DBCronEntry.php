<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\model;

/**
 * Description of DBCronJob
 *
 * @author drweb
 */
class DBCronEntry extends \losthost\DB\DBObject {
    
    const TABLE_NAME = 'telle_cron_entries';
    
    const SQL_CREATE_TABLE = <<<END
            CREATE TABLE IF NOT EXISTS %TABLE_NAME% (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                cron_expression varchar(100),
                next_start_time datetime,
                start_in_background tinyint(1) NOT NULL DEFAULT 0,
                last_started datetime,
                last_result varchar(10),
                last_error_description varchar(500),
                job_class varchar(300),
                job_args varchar(1024),
                PRIMARY KEY (id)
            ) COMMENT = 'v1.0.0'
            END;
    
    public function __construct(int | string $id_or_expression) {
        if (is_int($id_or_expression)) {
            // load by int
            parent::__construct('id = ?', $id_or_expression);
        } else {
            // create by expression
            parent::__construct();
            $this->cron_expression = $id_or_expression;
        }
    }
    
    protected function setNextStartTime() {
        $expr = new \Cron\CronExpression($this->cron_expression);
        $this->next_start_time = $expr->getNextRunDate();
    }
    
    protected function beforeInsert($comment, $data) {
        $this->setNextStartTime();
        parent::beforeInsert($comment, $data);
    }
    
    protected function beforeUpdate($comment, $data) {
        $this->setNextStartTime();
        parent::beforeUpdate($comment, $data);
    }
        
}
