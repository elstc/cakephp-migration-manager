<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;

/**
 * Test suite bootstrap for Elastic/MigrationManager.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */
$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception("Cannot find the root of the application, unable to run tests");
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

require $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';
ConnectionManager::setConfig('default', ['url' => getenv('db_dsn')]);

// Disable deprecations for now when using 3.6
if (version_compare(Configure::version(), '3.6.0', '>=')) {
    error_reporting(E_ALL ^ E_USER_DEPRECATED);
}

Plugin::load('Migrations', ['path' => dirname(__DIR__) . DS . 'vendor/cakephp/migrations/']);
Plugin::load('Elastic/MigrationManager', ['path' => dirname(__DIR__) . DS, 'bootstrap' => true]);

error_reporting(E_ALL);
