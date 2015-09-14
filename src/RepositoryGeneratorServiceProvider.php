<?php

namespace VTalbot\RepositoryGenerator;

use Illuminate\Support\ServiceProvider;
use VTalbot\RepositoryGenerator\Console\RepositoryMakeCommand;

class RepositoryGeneratorServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/repository.php' => config_path('repository.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCreator();
        $this->registerMakeCommand();
    }

    /**
     * Register the repository creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('repository.creator', function ($app) {
            return new RepositoryCreator($app['files']);
        });
    }

    /**
     * Register the "make" repository command.
     *
     * @return void
     */
    protected function registerMakeCommand()
    {
        $this->app->singleton('command.repository.make', function ($app) {
            $prefix = $app['config']['repository.prefix'];
            $suffix = $app['config']['repository.suffix'];
            $contract = $app['config']['repository.contract'];
            $namespace = $app['config']['repository.namespace'];
            $creator = $app['repository.creator'];

            return new RepositoryMakeCommand($creator, $prefix, $suffix, $contract, $namespace);
        });

        $this->commands('command.repository.make');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.repository.make',
        ];
    }

}
