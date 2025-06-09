# MigrationManager plugin for CakePHP

<p align="center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://github.com/elstc/cakephp-migration-manager/actions" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/github/actions/workflow/status/elstc/cakephp-migration-manager/ci.yml?style=flat-square">
    </a>
    <a href="https://codecov.io/gh/elstc/cakephp-migration-manager" target="_blank">
        <img alt="Codecov" src="https://img.shields.io/codecov/c/github/elstc/cakephp-migration-manager.svg?style=flat-square">
    </a>
    <a href="https://packagist.org/packages/elstc/cakephp-migration-manager" target="_blank">
        <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/elstc/cakephp-migration-manager.svg?style=flat-square">
    </a>
</p>

This plugin provides a GUI for database migrations via a web browser.

## IMPORTANT NOTICE

This plugin allows running migrations directly from a web browser,
which means some operations may delete or break data.
You should install this only when CLI cannot be used due to server restrictions.
Also, when installing, set up authentication and authorization appropriately
so that unauthorized users cannot execute it.

## Features

- List of Application / Plugins migration statuses
- Run migrate / rollback a migration
- Show migration file

## Version Map

| CakePHP Version | Plugin Version | Branch         |
|-----------------|----------------|----------------|
| 5.x             | 3.x            | cake5          |
| 4.x             | 2.x            | cake4          |
| 3.x             | 1.x            | cake3          |

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require elstc/cakephp-migration-manager
```

Load the plugin by adding the following statement in your project's `Application::bootstrap()` (open `src/Application.php`):

```
\Cake\Core\Configure::write('Elastic/MigrationManager.baseController', \App\Controller\AppController::class);
$this->addPlugin('Elastic/MigrationManager');
```

NOTE: If you don't need to migrate, you should comment out `$this->addPlugin('Elastic/MigrationManager')` to disable the plugin.

## Configure key: Elastic/MigrationManager.baseController

Specify the base class of MigrationManager controller. The default is `\Cake\Controller\Controller`.
Be sure to set it before loading the plugin because it will be used in the bootstrap of the plugin.

eg）Specify the base class to `App\Controller\Admin\BaseController`:

```php
Configure::write('Elastic/MigrationManager.baseController', \App\Controller\Admin\BaseController::class);
```

## Usage

You can use this plugin by accessing `https://{your-app-host}/migration-manager/migrations` from a browser.

### Allow rollback

By default, you can't call the rollback action.
If you want to enable rollback, add the following statement to your project's `config/bootstrap.php` file:

```php
Configure::write('Elastic/MigrationManager.canRollback', true);
```
