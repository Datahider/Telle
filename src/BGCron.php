<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;
use losthost\telle\model\DBCronEntry;
use losthost\telle\model\DBPendingJob;
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
        ini_set("error_log", "log/worker.log");        
        if (empty($sleep)) {
            $sleep = Bot::param('cron_sleep_time', 10);
        }
        $this->sleep = $sleep;
        parent::__construct($sleep);
        DBCronEntry::initDataStructure();
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
        $sql_cron = <<<END
                SELECT id FROM [telle_cron_entries] WHERE next_start_time <= ?
                END;
        $sql_pending = <<<END
                SELECT id FROM [telle_pending_jobs] WHERE start_time <= ? AND was_started IS NULL
                END;
        
        $now = date_create()->format(\losthost\DB\DB::DATE_FORMAT);
        $jobs = [];

        $jobs_cron = new \losthost\DB\DBView($sql_cron, $now);
        while ($jobs_cron->next()) {
            $jobs[] = new DBCronEntry($jobs_cron->id);
        }

        $jobs_pending = new \losthost\DB\DBView($sql_pending, $now);
        while ($jobs_pending->next()) {
            $jobs[] = new model\DBPendingJob($jobs_pending->id);
        }

        return $jobs;
    }

    protected function startJob(DBCronEntry|DBPendingJob &$job) {
        if (is_a($job, model\DBCronEntry::class)) {
            $this->startCronJob($job);
        } elseif (is_a($job, model\DBPendingJob::class)) {
            $this->startPendingJob($job);
        }
    }
    
    protected function startCronJob(DBCronEntry &$job) {
        if ($job->start_in_background) {
            Bot::logComment("CRON: Starting job \"$job->job_class\" in background", __FILE__, __LINE__);
            Bot::startClass($job->job_class, $job->job_args);
            Bot::logComment("CRON: \"$job->job_class\" started in background");

            $job->last_started = date_create();
            $job->last_result = self::JOB_RESULT_BACKGROUND;
        } else {
            Bot::logComment("CRON: Starting job \"$job->job_class\" in cron thread", __FILE__, __LINE__);
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
    
    protected function startPendingJob(DBPendingJob &$job) {
        if ($job->start_in_background) {
            Bot::logComment("CRON: Starting job \"$job->job_class\" in background", __FILE__, __LINE__);
            Bot::startClass($job->job_class, $job->job_args);

            $job->was_started = date_create();
            $job->result = self::JOB_RESULT_BACKGROUND;
        } else {
            Bot::logComment("CRON: Starting job \"$job->job_class\" in cron thread", __FILE__, __LINE__);
            $job_object = new ($job->job_class)($job->job_args);
            try {
                $job->was_started = date_create();
                $job_object->run();
                $job->result = self::JOB_RESULT_OK;
            } catch (\Exception $ex) {
                $job->result = self::JOB_RESULT_ERROR;
                $job->error_description = $ex->getMessage();
            }
        }
    }
    
    public function runJobs(array $jobs) {
        foreach ($jobs as $job) {
            if (class_exists($job->job_class)) {
                $this->startJob($job);
            } elseif (is_a($job, model\DBCronEntry::class)) {
                $job->last_started = date_create();
                $job->last_result = self::JOB_RESULT_ERROR;
                $job->last_error_description = "Class \"$job->job_class\" does not exist.";
                Bot::logComment("CRON: Class \"$job->job_class\" does not exist", __FILE__, __LINE__);
            } elseif (is_a($job, model\DBPendingJob::class)) {
                $job->was_started = date_create();
                $job->result = self::JOB_RESULT_ERROR;
                $job->error_description = "Class \"$job->job_class\" does not exist.";
                error_log("CRON: Class \"$job->job_class\" does not exist", __FILE__, __LINE__);
            }
            $job->write();
        } 
    }
    
    public function initNewJobs() {
        $sql = <<<END
                SELECT id FROM [telle_cron_entries] WHERE next_start_time IS NULL
                END;
        $jobs_to_init = new \losthost\DB\DBView($sql);
        
        while ($jobs_to_init->next()) {
            $job = new model\DBCronEntry($jobs_to_init->id);
            $job->write();
        }
    }
}
