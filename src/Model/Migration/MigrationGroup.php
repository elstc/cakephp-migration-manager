<?php
/*
 * Copyright 2026 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Model\Migration;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Elastic\MigrationManager\Model\Entity\MigrationStatus;
use Migrations\Migrations;
use Migrations\Util\Util;
use RuntimeException;
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
     * @var string|null
     */
    private ?string $plugin;

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

        $isPlugin = $name !== Configure::read('App.namespace');
        $this->plugin = $isPlugin ? $name : null;

        $migrationsConfig = [
            'connection' => $connection ?: 'default',
            'plugin' => $this->plugin,
        ];

        $this->migrations = new Migrations($migrationsConfig);

        // cakephp/migrations 4.x では setInput() が必要
        if (method_exists($this->migrations, 'setInput')) {
            $this->migrations->setInput($this->buildInput($name, $connection));
        }
    }

    /**
     * Inputオブジェクトの構築（cakephp/migrations 4.x 用）
     *
     * @param string $name the app / plugin name
     * @param string|null $connection the connection name
     * @return mixed
     */
    private function buildInput(string $name, ?string $connection = null): mixed
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
     * ReflectionClass を使わずファイルパス一覧からバージョン番号でマッチさせることで、
     * BaseMigration ベースのマイグレーションファイルにも対応する。
     *
     * @param string $id migration ID
     * @return string
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public function getFileContent(string $id): string
    {
        $migrationPaths = $this->getMigrationPaths();
        $phpFiles = Util::getFiles($migrationPaths);

        foreach ($phpFiles as $filePath) {
            $fileName = basename($filePath);
            if (!Util::isValidMigrationFileName($fileName)) {
                continue;
            }

            $version = (string)Util::getVersionFromFileName($fileName);
            if ($version === $id) {
                $content = file_get_contents($filePath);
                if ($content === false) {
                    throw new RuntimeException(
                        __d('elastic.migration_manager', 'Failed to read migration file: {0}', $filePath),
                    );
                }

                return $content;
            }
        }

        throw new NotFoundException(__d('elastic.migration_manager', 'Migration Not Found. ID: {0}', $id));
    }

    /**
     * マイグレーションパスの取得
     *
     * @return array<string>
     */
    private function getMigrationPaths(): array
    {
        if ($this->plugin !== null) {
            return [Plugin::configPath($this->plugin) . 'Migrations'];
        }

        return [CONFIG . 'Migrations'];
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
            'migrations' => $this->getMigrations()->toList(),
        ];
    }
}
