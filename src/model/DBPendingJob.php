<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle\model;
use losthost\DB\DB;

/**
 * Description of DBPendingJob
 *
 * @author drweb
 */
class DBPendingJob extends \losthost\DB\DBObject {

const METADATA = [
    'id' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
    'start_time' => 'datetime',
    'start_in_background' => 'tinyint(1) NOT NULL DEFAULT 0',
    'was_started' => 'datetime',
    'result' => 'varchar(10)',
    'error_description' => 'varchar(500)',
    'job_class' => 'varchar(300)',
    'job_args' => 'varchar(1024)',
    'PRIMARY KEY' => 'id'
];    
    
    public static function tableName() {
        return DB::$prefix. 'telle_pending_jobs';
    }
    
    public function __construct(int|\DateTime|\DateTimeImmutable $id_or_start_time, bool $start_in_background=false, $job_class='', $job_args='') {
        if (is_int($id_or_start_time)) {
            // load by int
            parent::__construct(['id' => $id_or_start_time]);
        } elseif (!empty ($job_class)) {
        // create 
            parent::__construct();
            $this->start_time = $id_or_start_time;
            $this->start_in_background = $start_in_background;
            $this->job_class = $job_class;
            $this->job_args = $job_args;
            $this->write();
        } else {
            throw new \Exception('You must give a job_class as the third argument.');
        }
    }
    
}
