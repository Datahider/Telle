<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\model;

/**
 * Description of DBPendingJob
 *
 * @author drweb
 */
class DBPendingJob extends \losthost\DB\DBObject {

    const TABLE_NAME = 'telle_pending_jobs';
    
    const SQL_CREATE_TABLE = <<<END
            CREATE TABLE IF NOT EXISTS %TABLE_NAME% (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                start_time datetime,
                start_in_background tinyint(1) NOT NULL DEFAULT 0,
                was_started datetime,
                result varchar(10),
                error_description varchar(500),
                job_class varchar(300),
                job_args varchar(1024),
                PRIMARY KEY (id)
            ) COMMENT = 'v1.0.0'
            END;
    
    public function __construct($id_or_start_time, $start_in_background=0, $class='', $args=[]) {
        if (is_int($id_or_expression)) {
            // load by int
            parent::__construct('id = ?', $id_or_expression);
            $this->loadByInt($id_or_expression);
        } else {
            // create by expression
            parent::__construct();
            $this->cron_expression = $id_or_expression;
        }
    }
    
}
