<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of BGCron
 *
 * @author drweb
 */
class BGCron extends abst\AbstractBackgroundProcess {
    
    const JOB_RESULT_OK = 'ok';
    const JOB_RESULT_ERROR = 'error';
    const JOB_RESULT_BACKGROUND = 'background';
    const JOB_RESULT_NA = 'n/a';

    protected $sleep;
    
    public function __construct($sleep=null) {
        if ($sleep === null) {
            $sleep = Bot::param('cron_sleep_time', 10);
        }
        $this->sleep = $sleep;
        parent::__construct($sleep);
        model\DBCronEntry::initDataStructure();
    }

    public function run() {
        
        $alive = new model\DBBotParam('cron_alive', time());
        
        while (1) {
            if (!Bot::isAlive('bot', Bot::param('bot_alive_timeout', 15))) {
                die('The bot is not alive.');
            }
            $alive->value = time();
            $this->initNewJobs();
            $jobs = $this->getJobs();
            $this->runJobs($jobs);
            sleep($this->sleep);
        }
    }
    
    public function getJobs() : array {
        $sql = <<<END
                SELECT id FROM [telle_cron_entries] WHERE next_start_time <= ?
                END;
        
        $now = date_create()->format(\losthost\DB\DB::DATE_FORMAT);
        $jobs = new \losthost\DB\DBView($sql, $now);
        $ids = [];
        while ($jobs->next()) {
            $ids[] = $jobs->id;
        }
        return $ids;
    }

    protected function startJob($job) {
        if ($job->start_in_background) {
            error_log("CRON: Starting job \"$job->job_class\" in background.");
            Bot::startClass($job->job_class, $job->job_args);

            $job->last_started = date_create();
            $job->last_result = self::JOB_RESULT_BACKGROUND;
        } else {
            error_log("CRON: Starting job \"$job->job_class\" in cron thread.");
            $job_object = new ($job->job_class)($job->job_args);
            try {
                $job->last_started = date_create();
                $job_object->run();
                $job->last_result = self::JOB_RESULT_OK;
            } catch (\Exception $ex) {
                $job->last_result = self::JOB_RESULT_ERROR;
                $job->last_error_description = $ex->getMessage();
            }
        }
    }
    
    public function runJobs(array $jobs) {
        foreach ($jobs as $job_id) {
            $job = new model\DBCronEntry($job_id);
            if (class_exists($job->job_class)) {
                $this->startJob($job);
            } else {
                $job->last_started = date_create();
                $job->last_result = self::JOB_RESULT_ERROR;
                $job->last_error_description = "Class \"$job->job_class\" does not exist.";
                error_log("CRON: Class \"$job->job_class\" does not exist.");
            }
            $job->write();
        } 
    }
    
    public function initNewJobs() {
        $sql = <<<END
                SELECT id FROM [cron_entries] WHERE next_start_time IS NULL
                END;
        $jobs_to_init = new \losthost\DB\DBView($sql);
        
        while ($jobs_to_init->next()) {
            $job = new model\DBCronEntry($jobs_to_init->id);
            $job->write();
        }
    }
}
