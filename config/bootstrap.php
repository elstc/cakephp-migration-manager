<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;

if (!class_exists('Elastic\MigrationManager\Controller\BaseController')) {
    class_alias(Configure::read(
        'Elastic/MigrationManager.baseController',
        Controller::class
    ), 'Elastic\MigrationManager\Controller\BaseController');
}

// back port for CakePHP < 3.6
if (!class_exists('Cake\Http\Exception\NotFoundException')) {
    class_alias('Cake\Network\Exception\NotFoundException', 'Cake\Http\Exception\NotFoundException');
}

// Load Migrations plugin
$errorReporting = error_reporting();
if (version_compare(Configure::version(), '3.6.0', '>=')) {
    error_reporting(E_ALL ^ E_USER_DEPRECATED);
}
if (!Plugin::loaded('Migrations')) {
    Plugin::load('Migrations');
}
error_reporting($errorReporting);
