# MigrationManager plugin for CakePHP

<p align="center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://github.com/elstc/cakephp-migration-manager/actions" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/github/workflow/status/elstc/cakephp-migration-manager/CakePHP%20Plugin%20CI?style=flat-square">
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

This plugin can be run migration from the web browser directly
so some operation may deleting/breaking data,
You install this only when CUI cannot be used due to server restrictions.
Also, when installing, setup authentication and authorization appropriately,
so that unauthorized users cannot execute it.

## Features

- List of Application / Plugins migration statuses
- Run migrate / rollback a migration
- Show migration file

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require elstc/cakephp-migration-manager
```

Load the plugin by adding the following statement in your project's `src/Application.php`:

```
\Cake\Lib\Configure::write('Elastic/MigrationManager.baseController', \App\Controller\AppController::class);
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

You can be used by accessing `https://{your-app-host}/migration-manager/migrations` from a browser.

### Allow rollback

In default, you can't call rollback action.
If you want to rollback, adding the following statement in your project's `config/bootstrap.php` file:

```php
Configure::write('Elastic/MigrationManager.canRollback', true);
```
