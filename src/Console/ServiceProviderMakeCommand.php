<?php

namespace VTalbot\RepositoryGenerator\Console;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ServiceProviderMakeCommand extends Command
{

    use AppNamespaceDetectorTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:repository:service {name=RepositoryServiceProvider : Name of the service provider.}
                                                    {--namespace=Providers : Namespace inside the application.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository service provider class.';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new repository service provider make command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $className = $this->getClassName();
        $namespace = $this->getNamespace();
        $fileName = $this->getFileName($namespace . '\\' . $className);

        if ($this->files->exists(app_path($fileName))) {
            $this->error('A service provider named "'.$className.'" already exists.');
            return;
        }

        $stub = $this->compileStub([
            'namespace' => $namespace,
            'class' => $className
        ]);

        $this->files->put(app_path($fileName), $stub);

        $this->info('Service provider successfully created, add the following to your providers in "app/config.php":');
        $this->line($namespace.'\\'.$className.'::class,');
    }

    /**
     * Compile the stub with the given data.
     *
     * @param  array $data
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function compileStub(array $data)
    {
        $stub = $this->files->get(__DIR__.'/../../stub/service.stub');

        foreach ($data as $key => $value) {
            $stub = str_replace('{{'.$key.'}}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get the class name.
     *
     * @return string
     */
    protected function getClassName()
    {
        return $this->argument('name');
    }

    /**
     * Get the namespace.
     *
     * @return string
     */
    protected function getNamespace()
    {
        return $this->getAppNamespace() . $this->option('namespace');
    }

    /**
     * Get the file name for the given class name.
     *
     * @param  string $className
     * @return string
     */
    protected function getFileName($className)
    {
        return str_replace('\\', DIRECTORY_SEPARATOR, str_replace($this->getAppNamespace(), '', $className)) . '.php';
    }

}
