<?php

namespace VTalbot\RepositoryGenerator;

use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

class RepositoryCreator
{

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The methods available.
     *
     * @var array
     */
    protected $methods = ['all', 'find', 'create', 'update', 'delete'];

    /**
     * Create a new instance of the repository creator.
     *
     * @param  \Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Create the repository and contract.
     *
     * @param  ReflectionClass $modelReflection
     * @param  string $classFileName
     * @param  string $shortClassName
     * @param  string $classNamespace
     * @param  string $contractFileName
     * @param  string|bool $shortContractName
     * @param  string|bool $contractNamespace
     * @param  array $methods
     * @return array
     */
    public function create(
        ReflectionClass $modelReflection,
        $classFileName,
        $shortClassName,
        $classNamespace,
        $contractFileName,
        $shortContractName,
        $contractNamespace,
        array $methods
    ) {
        $createdFiles = [$this->createRepository(
            $modelReflection,
            $classFileName,
            $shortClassName,
            $classNamespace,
            $shortContractName,
            $contractNamespace,
            $methods
        )];

        if ($shortContractName) {
            $createdFiles[] = $this->createContract(
                $modelReflection,
                $contractFileName,
                $shortContractName,
                $contractNamespace,
                $methods
            );
        }

        return $createdFiles;
    }

    /**
     * Check if the repository already exists.
     *
     * @param  string $path
     * @return bool
     */
    public function isNew($path)
    {
        return !$this->files->exists(app_path($path));
    }

    /**
     * Create the repository.
     *
     * @param  ReflectionClass $model
     * @param  string $fileName
     * @param  string $shortName
     * @param  string $namespace
     * @param  string|bool $contractName
     * @param  string|bool $contractNamespace
     * @param  array $methods
     * @return string
     */
    protected function createRepository(
        ReflectionClass $model,
        $fileName,
        $shortName,
        $namespace,
        $contractName,
        $contractNamespace,
        array $methods
    ) {
        $path = app_path($fileName);

        $stub = $this->compileStub('repository.stub', [
            'namespace'          => $namespace,
            'class'              => $shortName,
            'model.fullname'     => $model->getName(),
            'model'              => $model->getShortName(),
            'implementation'     => $this->getImplementationClass($contractName),
            'implementation.use' => $this->getImplementationUse($contractNamespace, $contractName),
        ]);

        $stub = $this->compileMethods($stub, $methods);

        $this->files->makeDirectory(dirname($path), 0755, true, true);

        $this->files->put($path, $stub);

        return $namespace . '\\' . $shortName;
    }

    /**
     * Create the contract for the repository.
     *
     * @param  ReflectionClass $model
     * @param  string $fileName
     * @param  string $shortName
     * @param  string $namespace
     * @param  array $methods
     * @return string
     */
    protected function createContract(ReflectionClass $model, $fileName, $shortName, $namespace, array $methods)
    {
        $path = app_path($fileName);

        $stub = $this->compileStub('contract.stub', [
            'namespace'      => $namespace,
            'class'          => $shortName,
            'model.fullname' => $model->getName(),
            'model'          => $model->getShortName(),
        ]);

        $stub = $this->compileMethods($stub, $methods);

        $this->files->makeDirectory(dirname($path), 0755, true, true);

        $this->files->put($path, $stub);

        return $namespace . '\\' . $shortName;
    }

    /**
     * Compile the stub with the given methods to keep.
     *
     * @param  string $stub
     * @param  array $methods
     * @return string
     */
    protected function compileMethods($stub, array $methods)
    {
        foreach ($methods as $method) {
            $stub = str_replace(['{{' . $method . '}}', '{{/' . $method . '}}'], '', $stub);
        }

        foreach ($this->methods as $method) {
            $stub = preg_replace('/{{' . $method . '}}(.*){{\/' . $method . '}}(\r\n)?/s', '', $stub);
        }

        return $stub;
    }

    /**
     * Compile the stub with the given data.
     *
     * @param  string $stub
     * @param  array $data
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function compileStub($stub, array $data)
    {
        $stub = $this->files->get(__DIR__ . '/../stub/' . $stub);

        foreach ($data as $key => $value) {
            $stub = str_replace('{{' . $key . '}}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get the implementation of the contract.
     *
     * @param  string|bool $contractName
     * @return string
     */
    protected function getImplementationClass($contractName)
    {
        return $contractName ? " implements $contractName" : '';
    }

    /**
     * Get the "use" of the implementation of the interface.
     *
     * @param  string|bool $namespace
     * @param  string|bool $contract
     * @return string
     */
    protected function getImplementationUse($namespace, $contract)
    {
        return $namespace ? "use $namespace\\$contract;" : '';
    }

}
