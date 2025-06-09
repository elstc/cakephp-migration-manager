<?php
/*
 * Copyright 2025 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Model\Migration;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Elastic\MigrationManager\Model\Entity\MigrationStatus;
use Migrations\Migrations;
use Phinx\Config\ConfigInterface;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use function Cake\I18n\__d;

/**
 * マイグレーショングループ
 */
class MigrationGroup
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var \Migrations\Migrations
     */
    private Migrations $migrations;

    /**
     * MigrationGroup constructor.
     *
     * @param string $name the app / plugin name
     * @param string|null $connection the connection name
     */
    public function __construct(string $name, ?string $connection = null)
    {
        $this->name = $name;

        $migrationsConfig = [
            'connection' => $connection ?: 'default',
            'plugin' => null,
        ];
        if ($name !== Configure::read('App.namespace')) {
            $migrationsConfig['plugin'] = $name;
        }

        $this->migrations = new Migrations($migrationsConfig);
        $this->migrations->setInput($this->buildInput($name, $connection));
    }

    /**
     * Inputオブジェクトの構築
     *
     * @param string $name the app / plugin name
     * @param string|null $connection the connection name
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    private function buildInput(string $name, ?string $connection = null): InputInterface
    {
        $args = [];
        if ($name !== Configure::read('App.namespace')) {
            $args['--plugin'] = $name;
        }
        if ($connection) {
            $args['--connection'] = $connection;
        }

        return (new InputBuilder())->build($args);
    }

    /**
     * マイグレーションマネージャー設定の取得
     *
     * @return \Phinx\Config\ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->migrations->getConfig();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * マイグレーションリストの取得
     *
     * @return \Cake\Collection\CollectionInterface<\Elastic\MigrationManager\Model\Entity\MigrationStatus>
     * @throws \Exception
     */
    public function getMigrations(): CollectionInterface
    {
        $statuses = $this->migrations->status();
        $migrations = array_map(static function ($status) {
            return new MigrationStatus($status);
        }, $statuses);

        return new Collection($migrations);
    }

    /**
     * 最終のマイグレーション
     *
     * @return \Elastic\MigrationManager\Model\Entity\MigrationStatus|null
     * @throws \Exception
     */
    public function getLastMigration(): ?MigrationStatus
    {
        return $this->getMigrations()->last();
    }

    /**
     * 指定バージョンまでマイグレーションを実行する
     *
     * @param string|int $id migration ID
     * @return bool
     * @throws \Exception
     */
    public function migrateTo(string|int $id): bool
    {
        return $this->migrations->migrate(['target' => $id]);
    }

    /**
     * 指定バージョンをロールバックする
     *
     * @param string|int $id migration ID
     * @return bool
     * @throws \Exception
     */
    public function rollback(string|int $id): bool
    {
        return $this->migrations->rollback(['target' => $id]);
    }

    /**
     * シードを実行する
     *
     * @param string|null $seed seed name
     * @return bool
     * @throws \Exception
     */
    public function seed(?string $seed = null): bool
    {
        return $this->migrations->seed(['seed' => $seed]);
    }

    /**
     * マイグレーションファイルの内容を取得する
     *
     * @param string $id migration ID
     * @return string
     * @throws \Cake\Http\Exception\NotFoundException
     * @throws \Exception
     */
    public function getFileContent(string $id): string
    {
        $manager = $this->migrations->getManager($this->getConfig());
        $migrations = $manager->getMigrations($manager->getConfig()->getDefaultEnvironment());

        $migration = null;
        foreach ($migrations as $version => $_migration) {
            if ($version === (int)$id) {
                $migration = $_migration;
                break;
            }
        }

        if (!$migration) {
            throw new NotFoundException(__d('elastic.migration_manager', 'Migration Not Found. ID: {0}', $id));
        }

        $reflection = new ReflectionClass($migration);
        $fileName = $reflection->getFileName();

        if (!$fileName) {
            throw new NotFoundException(
                __d('elastic.migration_manager', 'Migration file not found for ID: {0}', $id),
            );
        }

        return (string)file_get_contents($fileName);
    }

    /**
     * change connection
     *
     * @param string $connection target connection name
     * @return self
     */
    public function withConnection(string $connection): self
    {
        return new self($this->name, $connection);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'config' => $this->getConfig(),
            'migrations' => $this->getMigrations()->toList(),
        ];
    }
}
