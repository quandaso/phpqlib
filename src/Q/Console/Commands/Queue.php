<?php
/**
 * @author quantm.tb@gmail.com
 * @date: 12/23/2016 10:35 AM
 */

namespace Q\Console\Commands;



use Q\Console\Cmd;
use Q\Console\Jobs\QueueJob;


class Queue extends Cmd
{
    public function main($sleep = 3) {
        $this->createTableIfNotExists();
        $_db = db();
        $db = clone $_db;
        $sleep = $sleep > 0 ? $sleep : 3;
        while(true) {
            $jobs = $db->table('queue_jobs')->where('status', 'waiting')->get();
            foreach ($jobs as $job) {
                echo (sprintf("[%s] Processed %s - %s..", date('Y-m-d H:i:s'), $job->id, $job->name));
                try {
                    $result = QueueJob::dispatch($job, 1);
                    echo $result ? "OK\n" : "ERROR\n";
                } catch(\Exception $e) {
                    echo "ERROR: " . $e->getMessage() . "\n";
                    echo $e->getTraceAsString() . "\n";
                }

            }

            sleep($sleep);
        }

    }

    /**
     * Create queue job table if not exists
     */
    public function createTableIfNotExists() {
        $db = db();
        $tables = $db->getAllTables();
        if (!in_array('queue_jobs', $tables)) {
            $db->query("CREATE TABLE `queue_jobs` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `class` varchar(100) NOT NULL,
                      `data` text,
                      `status` enum('waiting','finished','failed') NOT NULL DEFAULT 'waiting',
                      `created_at` datetime DEFAULT NULL,
                      `finished_at` datetime DEFAULT NULL,
                      `attempts` int(11) DEFAULT '0',
                      `errors` text,
                      `result` text,
                      PRIMARY KEY (`id`),
                      KEY `name` (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }


    }
}