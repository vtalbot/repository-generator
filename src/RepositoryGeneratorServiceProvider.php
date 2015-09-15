<?php

namespace VTalbot\RepositoryGenerator;

use Illuminate\Support\ServiceProvider;
use VTalbot\RepositoryGenerator\Console\RepositoryMakeCommand;
use VTalbot\RepositoryGenerator\Console\ServiceProviderMakeCommand;

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
        $this->registerMakeServiceCommand();
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
            $files = $app['files'];

            return new RepositoryMakeCommand($creator, $prefix, $suffix, $contract, $namespace, $files);
        });

        $this->commands('command.repository.make');
    }

    protected function registerMakeServiceCommand()
    {
        $this->app->singleton('command.repository.service', function($app) {
            return new ServiceProviderMakeCommand($app['files']);
        });

        $this->commands('command.repository.service');
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
            'command.repository.service',
        ];
    }

}
