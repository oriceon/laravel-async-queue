<?php

namespace Oriceon\Queue\Console;

use Oriceon\Queue\AsyncQueue;
use Illuminate\Console\Command;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

class AsyncCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:async';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a queue job from the database';

	/**
	 * Create a new queue listen command.
	 *
	 * @param Worker $worker
	 */
	public function __construct(protected Worker $worker)
	{
		parent::__construct();
	}

    /**
     * Execute the console command.
     *
     * @param WorkerOptions $options
     * @return void
     */
    public function handle(WorkerOptions $options): void
    {
        $id = $this->argument('id');
        $connection = $this->argument('connection');
        
        $this->processJob(
			$connection, $id, $options
		);
    }

    /**
     *  Process the job
     *
     * @param string $connectionName
     * @param integer $id
     * @param WorkerOptions $options
     *
     * @throws Throwable
     */
    protected function processJob(string $connectionName, int $id, WorkerOptions $options)
    {
        $manager = $this->worker->getManager();

        /** @var AsyncQueue $connection */
        $connection = $manager->connection($connectionName);
        
		$job = $connection->getJobFromId($id);

		if ( ! is_null($job)) {
			$this->worker->process(
				$manager->getName($connectionName), $job, $options
			);
		}
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['id', InputArgument::REQUIRED, 'The Job ID'],
            ['connection', InputArgument::OPTIONAL, 'The name of connection'],
        ];
    }
}
