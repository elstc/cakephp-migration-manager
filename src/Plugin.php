<?php
/*
 * Copyright 2024 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager;

use Cake\Controller\Controller;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\BaseApplication;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/**
 * Plugin class for CakePHP.
 */
class Plugin extends BasePlugin
{
    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = true;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = true;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;

    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        if (!class_exists('Elastic\MigrationManager\Controller\BaseController')) {
            class_alias(Configure::read(
                'Elastic/MigrationManager.baseController',
                Controller::class
            ), 'Elastic\MigrationManager\Controller\BaseController');
        }

        if ($app instanceof BaseApplication && !$app->getPlugins()->has('Migrations')) {
            $app->addPlugin('Migrations');
        }
    }

    /**
     * @inheritDoc
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'Elastic/MigrationManager',
            ['path' => '/migration-manager'],
            static function (RouteBuilder $routes): void {
                $routes->fallbacks(DashedRoute::class);
            }
        );
    }
}
