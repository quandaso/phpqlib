<?php
/**
 * Created by PhpStorm.
 * User: quantm
 * Date: 12/22/2016
 * Time: 9:42 PM
 */

namespace Q\Console\Jobs;


class QueueJob
{
    public $name = 'QueueJob';
    private $data;
    public function __construct() {
        $this->data = [
            'class' => static::class,
            'name' => $this->name,
            'data' => serialize(func_get_args()),
            'created_at' => new \DateTime,
            'status' => 'waiting',
            'attempts' => 0
        ];

    }

    /**
     * @throws \Exception
     */
    public function enqueue() {
        db()->table('queue_jobs')->insert($this->data);
    }

    /**
     * Dispatch job
     * @param $id
     * @param $maxAttempt
     * @throws \Exception
     * @return bool
     */
    public static function dispatch($id, $maxAttempt = 3) {
        $db  = db();

        if (is_scalar($id)) {
            $job = $db->table('queue_jobs')->find($id);
        } else if (is_array($id) || $id instanceof \stdClass) {
            $job = $id;
        } else {
            throw new \Exception('Invalid job');
        }

        $class = $job->class;
        $args = unserialize($job->data);
        $reflect  = new \ReflectionClass($class);
        $instance = $reflect->newInstanceArgs($args);

        if (!($instance instanceof QueueJob)) {
            throw new \Exception('Queue job must be an instance of QueueJob');
        }

        $attempt = 0;
        $exception = null;
        while ($attempt < $maxAttempt) {
            try {
                $result = call_user_func_array([$instance, 'run'], $args);
                $db->table('queue_jobs')->query('UPDATE `queue_jobs` SET `attempts`=`attempts`+1,`status`=?,`finished_at`=?,`result`=? WHERE `id`=?', ['finished', date('Y-m-d H:i:s'), $result, $job->id]);
                break;
            } catch (\Exception $e) {

                if ($attempt === $maxAttempt -1) {
                    $error = [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'trace' => explode("\n", $e->getTraceAsString())
                    ];
                    $errorJson = json_encode($error, JSON_PRETTY_PRINT);

                    $db->table('queue_jobs')->query('UPDATE `queue_jobs` SET `attempts`=`attempts`+1, `errors`=?,`status`=? WHERE `id`=?', [$errorJson,'failed', $job->id]);
                    return false;
                } else {
                    $db->table('queue_jobs')->query('UPDATE `queue_jobs` SET `attempts`=`attempts`+1 WHERE `id`=?', [$job->id]);
                }

            }

            $attempt++;
        }

        return true;
    }
}