<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

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
     * @return CollectionInterface|MigrationGroup[]
     */
    public function fetchAll()
    {
        $collections = [];
        $collections[] = $this->createMigrationGroup();

        $plugins = Plugin::loaded();
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
     * @return MigrationGroup
     */
    private function createMigrationGroup($name = null)
    {
        if ($name === null) {
            $name = Configure::read('App.namespace');
        }

        return new MigrationGroup($name);
    }

    /**
     * プラグインがマイグレーションを含むかチェックする
     *
     * @param string $pluginName プラグイン名
     * @return bool
     */
    private function hasMigrations($pluginName)
    {
        $migrationPath = Plugin::configPath($pluginName) . 'Migrations';

        return is_dir($migrationPath) && count(glob($migrationPath . '/*.php'));
    }
}
