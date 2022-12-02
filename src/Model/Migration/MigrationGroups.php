<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Model\Migration;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Core\Plugin;

/**
 * マイグレーショングループ
 */
class MigrationGroups
{
    /**
     * @var string
     */
    private $connection;

    /**
     * @return \Cake\Collection\CollectionInterface|\Elastic\MigrationManager\Model\Migration\MigrationGroup[]
     */
    public function fetchAll(): CollectionInterface
    {
        $collections = [];
        $collections[] = $this->createMigrationGroup();

        $plugins = [];
        if (method_exists(Plugin::class, 'getCollection')) {
            foreach (Plugin::getCollection() as $plugin) {
                $plugins[] = $plugin->getName();
            }
            sort($plugins);
        } else {
            $plugins = Plugin::loaded();
        }

        foreach ($plugins as $pluginName) {
            if ($this->hasMigrations($pluginName)) {
                $collections[] = $this->createMigrationGroup($pluginName);
            }
        }

        // アプリケーションとプラグイン
        return new Collection($collections);
    }

    /**
     * MigrationGroupの生成
     *
     * @param string|null $name プラグイン名
     * @return \Elastic\MigrationManager\Model\Migration\MigrationGroup
     */
    private function createMigrationGroup(?string $name = null): MigrationGroup
    {
        if ($name === null) {
            $name = (string)Configure::read('App.namespace', 'App');
        }

        return new MigrationGroup($name, $this->connection);
    }

    /**
     * プラグインがマイグレーションを含むかチェックする
     *
     * @param string $pluginName プラグイン名
     * @return bool
     */
    private function hasMigrations(string $pluginName): bool
    {
        $migrationPath = Plugin::configPath($pluginName) . 'Migrations';

        return is_dir($migrationPath) && count(glob($migrationPath . '/*.php'));
    }

    /**
     * コネクションのセット
     *
     * @param string|null $connection 指定コネクション
     * @return self
     */
    public function withConnection(?string $connection): MigrationGroups
    {
        $new = clone $this;
        $new->connection = $connection;

        return $new;
    }
}
