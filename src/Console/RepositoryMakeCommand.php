<?php

namespace VTalbot\RepositoryGenerator\Console;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;
use VTalbot\RepositoryGenerator\Exceptions\NotAnInstanceOfException;
use VTalbot\RepositoryGenerator\RepositoryCreator;

class RepositoryMakeCommand extends Command
{

    use AppNamespaceDetectorTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:repository {model : The model class name.}
        {--name= : The name of the repository.}
        {--prefix= : The prefix to use with contract.}
        {--suffix= : The suffix to use after the model name.}
        {--contract : Create a contract for the repository.}
        {--only= : Keep only the specified methods.}
        {--except= : Remove the specified methods.}
        {--plain : Plain repository, without methods.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    /**
     * The repository creator instance.
     *
     * @var \VTalbot\RepositoryGenerator\RepositoryCreator
     */
    protected $creator;

    /**
     * Default prefix of the repository concrete class.
     *
     * @var string
     */
    protected $classNamePrefix;

    /**
     * Default suffix of the repository concrete and contract classes.
     *
     * @var string
     */
    protected $classNameSuffix;

    /**
     * Contract namespace.
     *
     * @var string
     */
    protected $contractNamespace;

    /**
     * Repository namesapce.
     *
     * @var string
     */
    protected $namespace;

    /**
     * Reflection of the model to use for the repository.
     *
     * @var ReflectionClass
     */
    protected $model;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new repository make command instance.
     *
     * @param  \VTalbot\RepositoryGenerator\RepositoryCreator $creator
     * @param  string $prefix
     * @param  string $suffix
     * @param  string $contract
     * @param  string $namespace
     * @param  Filesystem $files
     */
    public function __construct(RepositoryCreator $creator, $prefix, $suffix, $contract, $namespace, Filesystem $files)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->classNamePrefix = $prefix;
        $this->classNameSuffix = $suffix;
        $this->contractNamespace = $contract;
        $this->namespace = $namespace;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->model = $this->getModelReflection();

        $className = $this->getRepositoryName();
        $contractName = $this->getContractName();

        $shortClassName = $this->shorten($className);
        $shortContractName = $this->shorten($contractName);

        $classNamespace = $this->getNamespace($className);
        $contractNamespace = $this->getNamespace($contractName);

        $classFileName = $this->compileFileName($className);
        $contractFileName = $this->compileFileName($contractName);

        $methods = $this->getMethods();

        if (!$this->creator->isNew($classFileName)) {
            $this->error('The repository already exists: ' . app_path($classFileName));
            return;
        }

        $createdClasses = $this->creator->create(
            $this->model,
            $classFileName,
            $shortClassName,
            $classNamespace,
            $contractFileName,
            $shortContractName,
            $contractNamespace,
            $methods
        );

        $this->info('Repository created: ');
        foreach ($createdClasses as $class) {
            $this->line('> ' . $class);
        }

        if (count($createdClasses) > 1) {
            $this->registerWithServiceProvider($createdClasses[1], $createdClasses[0]);

            $this->info('Contract binding added into the repository config file.');
        }
    }

    protected function registerWithServiceProvider($contract, $repository)
    {
        $config = $this->files->get(config_path('repository.php'));

        if (array_key_exists($contract, config('repository.repositories'))) {
            return;
        }

        $contents = explode('\'repositories\' => [', $config);

        $content = join("'repositories' => [\n         $contract::class => $repository::class,", $contents);

        $this->files->put(config_path('repository.php'), $content);
    }

    /**
     * Get the methods to implement.
     *
     * @return array
     */
    protected function getMethods()
    {
        if ($this->option('plain')) {
            return [];
        }

        $methods = ['all', 'find', 'create', 'update', 'delete'];

        if (($only = $this->option('only')) && $only != '') {
            $methods = array_flip(array_only(array_flip($methods), $this->getArray($only)));
        }

        if (($except = $this->option('except')) && $except != '') {
            $methods = array_flip(array_except(array_flip($methods), $this->getArray($except)));
        }

        return $methods;
    }

    /**
     * Get an array of the given values.
     *
     * @param  string $values
     * @return array
     */
    protected function getArray($values)
    {
        $values = explode(',', $values);

        array_walk($values, function (&$value) {
            $value = trim($value);
        });

        return $values;
    }

    /**
     * Get a compiled file name for the given full class name.
     *
     * @param  string|bool $className
     * @return string|bool
     */
    protected function compileFileName($className)
    {
        if (!$className) {
            return false;
        }

        $className = str_replace($this->getAppNamespace(), '', $className);
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        return $className . '.php';
    }

    /**
     * Shorten the class or interface name.
     *
     * @param string $classPath
     * @return string
     */
    protected function shorten($classPath)
    {
        $parts = explode('\\', $classPath);

        return array_pop($parts);
    }

    /**
     * Get the contract interface name.
     *
     * @return string|bool
     */
    protected function getContractName()
    {
        if (!$this->input->getOption('contract')) {
            return false;
        }

        $className = $this->getClassName();

        return $this->getContractNamespace() . '\\' . $className;
    }

    /**
     * Get the repository name.
     *
     * @return string
     */
    protected function getRepositoryName()
    {
        $prefix = $this->getClassNamePrefix();
        $name = $this->getClassName();

        if ($this->input->getOption('contract')) {
            $name = $prefix . $name;
        }

        return $this->getNamespace() . '\\' . $name;
    }

    /**
     * Get class name.
     *
     * @return string
     */
    protected function getClassName()
    {
        $className = $this->input->getOption('name');

        if (empty($className)) {
            $className = $this->model->getShortName() . $this->getClassNameSuffix();
        }

        return $className;
    }

    /**
     * Get class name prefix.
     *
     * @return string
     */
    protected function getClassNamePrefix()
    {
        $prefix = $this->input->getOption('prefix');

        if (empty($prefix)) {
            $prefix = $this->classNamePrefix;
        }

        return $prefix;
    }

    /**
     * Get class name suffix.
     *
     * @return string
     */
    protected function getClassNameSuffix()
    {
        $suffix = $this->input->getOption('suffix');

        if (empty($suffix)) {
            $suffix = $this->classNameSuffix;
        }

        return $suffix;
    }

    /**
     * Get the model class name.
     *
     * @return ReflectionClass
     * @throws \VTalbot\RepositoryGenerator\Exceptions\NotAnInstanceOfException
     */
    protected function getModelReflection()
    {
        $modelClassName = str_replace('/', '\\', $this->input->getArgument('model'));

        try {
            $reflection = new ReflectionClass($this->getAppNamespace() . $modelClassName);
        } catch (\ReflectionException $e) {
            $reflection = new ReflectionClass($modelClassName);
        }

        if (!$reflection->isSubclassOf(Model::class) || !$reflection->isInstantiable()) {
            throw new NotAnInstanceOfException(sprintf('The model must be an instance of [%s]', Model::class));
        }

        return $reflection;
    }

    /**
     * Get the namespace of the repositories contract classes.
     *
     * @return string
     */
    protected function getContractNamespace()
    {
        return $this->getAppNamespace() . $this->contractNamespace;
    }

    /**
     * Get the namespace of the repositories classes.
     *
     * @return string
     */
    protected function getNamespace($namespace = null)
    {
        if (!is_null($namespace)) {
            $parts = explode('\\', $namespace);

            array_pop($parts);

            return join('\\', $parts);
        }

        return $this->getAppNamespace() . $this->namespace;
    }

}
