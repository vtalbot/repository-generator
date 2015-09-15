# Repository Generator for Laravel 5.1

This package offer the possibility to generate repository based on the give model.


## Installation

Via Composer

``` bash
$ composer require vtalbot/repository-generator --dev
```

Edit your `AppServiceProvider` to add the following to the `register` method:

``` php
    if ($this->app->environment() == 'local') {
        $this->app->register(\VTalbot\RepositoryGenerator\RepositoryGeneratorServiceProvider::class);
    }
```

Then execute the command:

``` bash
$ php artisan vendor:publish
```

Edit `config/repository.php` to your needs.


## Usage (based on default config values)

To create a simple repository:

``` bash
$ php artisan make:repository User
Repository created:
> App\Repositories\UserRepository
``` 

To have a contract with the repository:

``` bash
$ php artisan make:repository User --contract
Repository created:
> App\Repositories\DbUserRepository
> App\Repositories\Contracts\UserRepository
```

By default, the repository will have the methods: `all`, `find`, `create`, `update` and `delete`. You can
Change the methods that will be added by using the options `--only=all,find` or `--except=all,find`. If you
want a plain repository, use the option `--plain`.

A suffix is by default added to the repository, based on the config file. To change this value, you can provide
the option `--suffix=Repo`. There is an option for the prefix when using the option `--contract`, by providing
the option `--prefix=Ab`, which by default is `Db`.

If you want to change the name of the repository, you can use the option `--name=Users` to replace the model
name and suffix.

``` bash
$ php artisan make:repository User --name=Users --contract
Repository created:
> App\Repositories\DbUsers
> App\Repositories\Contracts\Users
```

If you want to automatically load the generated repositories, execute the following command:

``` bash
$ php artisan make:repository:service
```

It accept a `name` argument to define the name you want and a `--namespace` option if your providers aren't inside
the default `app/Providers`.


## Changelog

### v1.1.1

* Fix class reflection test order.

### v1.1.0

* Add repository service provider to load generated repositories with contract.