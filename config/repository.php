<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Class Prefix
    |--------------------------------------------------------------------------
    |
    | This is the default prefix to prepend to the class name when creating
    | contract for the repository at the same time.
    |
    */
    'prefix'    => 'Db',

    /*
    |--------------------------------------------------------------------------
    | Default Class Suffix
    |--------------------------------------------------------------------------
    |
    | This is the default suffix to append to the class name of the
    | repository, for example: User would become UserRepository.
    |
    */
    'suffix'    => 'Repository',

    /*
    |--------------------------------------------------------------------------
    | Default Contracts Namespace
    |--------------------------------------------------------------------------
    |
    | This is the default namespace for the contracts of the repositories.
    |
    */
    'contract'  => 'Repositories\Contracts',

    /*
    |--------------------------------------------------------------------------
    | Default Namespace
    |--------------------------------------------------------------------------
    |
    | This is the default namespace after the application namespace for the
    | repositories classes, for example: App\[Repositories]\DbUserRepository.
    |
    */
    'namespace' => 'Repositories',

    /*
    |--------------------------------------------------------------------------
    | Repositories Bindings
    |--------------------------------------------------------------------------
    |
    | This is the array containing the bindings, contract to concrete class.
    |
    */
    'repositories' => [
    ],

];
