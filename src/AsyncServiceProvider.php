<?php

namespace Oriceon\Queue;

use Illuminate\Queue\QueueManager;
use Oriceon\Queue\Connectors\AsyncConnector;
use Oriceon\Queue\Console\AsyncCommand;
use Illuminate\Support\ServiceProvider;

class AsyncServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Add the connector to the queue drivers.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerAsyncConnector($this->app['queue']);

        $this->commands('command.queue.async');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerAsyncCommand();
    }

    /**
     * Register the queue listener console command.
     *
     *
     * @return void
     */
    protected function registerAsyncCommand(): void
    {
        $this->app->singleton('command.queue.async', function () {
             return new AsyncCommand($this->app['queue.worker']);
        });
    }

    /**
     * Register the Async queue connector.
     *
     * @param QueueManager $manager
     *
     * @return void
     */
    protected function registerAsyncConnector(QueueManager $manager): void
    {
        $manager->addConnector('async', function () {
            return new AsyncConnector($this->app['db']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['command.queue.async'];
    }
}
