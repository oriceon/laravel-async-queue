<?php

namespace Oriceon\Queue;

use DateTime;
use Illuminate\Database\Connection;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;

class AsyncQueue extends DatabaseQueue
{
    public function __construct(
        Connection $database,
        string $table,
        string $default = 'default',
        int $expire = 60,
        protected string $binary = 'php',
        protected string|array $binaryArgs = '',
        protected $connectionName = ''
    )
    {
        parent::__construct($database, $table, $default, $expire);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string      $job
     * @param mixed       $data
     * @param string|null $queue
     *
     * @return int
     */
    public function push($job, $data = '', $queue = null): int
    {
        $id = parent::push($job, $data, $queue);
        $this->startProcess($id);

        return $id;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param null $queue
     * @param array $options
     *
     * @return int
     */
    public function pushRaw($payload, $queue = null, array $options = []): int
    {
        $id = parent::pushRaw($payload, $queue, $options);
        $this->startProcess($id);

        return $id;
    }
    
    /**
     * Push a new job onto the queue after a delay.
     *
     * @param DateTime|int  $delay
     * @param string        $job
     * @param mixed         $data
     * @param string|null   $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        $id = parent::later($delay, $job, $data, $queue);
        $this->startProcess($id);

        return $id;
    }
    
    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     *
     * @return array
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0): array
    {
        $record = parent::buildDatabaseRecord($queue, $payload, $availableAt, $attempts);
        $record['reserved_at'] = $this->currentTime();

        return $record;
    }
    
    /**
     * Get the next available job for the queue.
     *
     * @param  int $id
     *
     * @return DatabaseJob|null
     */
    public function getJobFromId(int $id): DatabaseJob|null
    {
        $job = $this->database->table($this->table)->where('id', $id)->first();
        if ($job) {
            $job = $this->markJobAsReserved(new DatabaseJobRecord($job));

            return new DatabaseJob(
                $this->container, $this, $job, $this->connectionName, $job->queue
            );
        }

        return null;
    }
    
    /**
     * Make a Process for the Artisan command for the job id.
     *
     * @param int $id
     *
     * @return void
     */
    public function startProcess(int $id): void
    {
        $command = $this->getCommand($id);

        $cwd = base_path();

        proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    }

    /**
     * Get the Artisan command as a string for the job id.
     *
     * @param int $id
     *
     * @return string
     */
    protected function getCommand(int $id): string
    {
        $connection = $this->connectionName;
        $cmd = '%s artisan queue:async %d %s';
        $cmd = $this->getBackgroundCommand($cmd);

        $binary = $this->getPhpBinary();

        return sprintf($cmd, $binary, $id, $connection);
    }

    /**
     * Get the escaped PHP Binary from the configuration
     *
     * @return string
     */
    protected function getPhpBinary(): string
    {
        $path = $this->binary;
        if ( ! defined('PHP_WINDOWS_VERSION_BUILD')) {
            $path = escapeshellarg($path);
        }

        $args = $this->binaryArgs;
        if (is_array($args)) {
            $args = implode(' ', $args);
        }

        return trim($path . ' ' . $args);
    }

    protected function getBackgroundCommand(string $cmd): string
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return 'start /B ' . $cmd . ' > NUL';
        }

        return $cmd . ' > /dev/null 2>&1 &';
    }
}
