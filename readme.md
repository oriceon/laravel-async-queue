# Laravel 9+ Async Queue Driver

## Push a function/closure to the background.

Just like the 'sync' driver, this is not a real queue driver. It is always fired immediatly.
The only difference is that the closure is sent to the background without waiting for the response.
This package is more usable as an alternative for running incidental tasks in the background, without setting up a 'real' queue driver.

> **Note:** This is using the DatabaseQueue, so make sure you set that up first, including migrations.

### Install

Require the latest version of this package with Composer

    composer require oriceon/laravel-async-queue

You need to create the migration table for queues and run it.

    $ php artisan queue:table
    $ php artisan migrate

You should now be able to use the async driver in config/queue.php. Use the same config as for the database, but use async as driver.

    'connections' => [
        ...
        'async' => [
            'driver' => 'async',
            'table'  => 'jobs',
            'queue'  => 'default',
            'expire' => 60,
        ],
        ...
    }

Set the default to `async`, either by changing to config or setting `QUEUE_DRIVER` in your `.env` file to `async`.

> Note: By default, `php` is used as the binary path to PHP. You can change this by adding the `binary` option to the queue config. You can also add extra arguments (for HHVM for example)

    'connections' => [
        ...
        'async' => [
            'driver'      => 'async',
            'table'       => 'jobs',
            'queue'       => 'default',
            'expire'      => 60,
            'binary'      => 'php',
            'binary_args' => '',
        ],
        ...
    }

It should work the same as the sync driver, so no need to run a queue listener. Downside is that you cannot actually queue or plan things. Queue::later() is also fired directly. For more info see http://laravel.com/docs/queues

# Credits to main author

Fwork package : [barryvdh/laravel-async-queue](https://github.com/barryvdh/laravel-async-queue)