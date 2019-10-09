<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::plugin(
    'Elastic/MigrationManager',
    ['path' => '/migration-manager'],
    static function (RouteBuilder $routes) {
        $routes->registerMiddleware('csrf', new CsrfProtectionMiddleware());
        $routes->applyMiddleware('csrf');

        $routes->fallbacks(DashedRoute::class);
    }
);
