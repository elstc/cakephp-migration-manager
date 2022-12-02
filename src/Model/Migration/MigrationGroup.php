<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Model\Migration;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\NotFoundException;
use Elastic\MigrationManager\Model\Entity\MigrationStatus;
use Migrations\CakeAdapter;
use Migrations\ConfigurationTrait;
use Phinx\Migration\Manager;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * マイグレーショングループ
 */
class MigrationGroup
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Phinx\Migration\Manager
     */
    private $manager;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * MigrationGroup constructor.
     *
     * @param string $name the app / plugin name
     * @param string|null $connection the connection name
     */
    public function __construct(string $name, ?string $connection = null)
    {
        $this->name = $name;

        $this->input = $this->buildInput($name, $connection);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
     * マイグレーションリストの取得
     *
     * @return \Cake\Collection\CollectionInterface|\Elastic\MigrationManager\Model\Entity\MigrationStatus[]
     * @throws \Exception
     */
    public function getMigrations(): CollectionInterface
    {
        $manager = $this->getManager();
        $statuses = $manager->printStatus($this->getConfig()->getDefaultEnvironment(), 'json');
        $migrations = array_map(static function ($status) {
            return new MigrationStatus($status);
        }, $statuses);

        return new Collection($migrations);
    }

    /**
     * @return \Phinx\Migration\Manager
     * @throws \Exception
     */
    private function getManager(): Manager
    {
        if ($this->manager === null) {
            $this->output = new BufferedOutput();
            $this->manager = new MigrationManager($this->getConfig(), $this->input, $this->output);
            $this->setAdapter($this->manager);
        }

        return $this->manager;
    }

    /**
     * Sets the adapter the manager is going to need to operate on the DB
     * This will make sure the adapter instance is a \Migrations\CakeAdapter instance
     *
     * @param \Phinx\Migration\Manager $manager the migration manager
     * @return void
     * @throws \Exception
     */
    private function setAdapter(Manager $manager): void
    {
        $env = $manager->getEnvironment('default');
        $input = $manager->getInput();
        $adapter = $env->getAdapter();

        if ($adapter instanceof CakeAdapter) {
            return;
        }

        $connectionName = 'default';
        if ($input !== null && $input->getOption('connection')) {
            $connectionName = $input->getOption('connection');
        }
        $connection = ConnectionManager::get($connectionName);
        if (!$connection instanceof Connection) {
            throw new \Exception('$connection must be ' . Connection::class);
        }

        $env->setAdapter(new CakeAdapter($adapter, $connection));
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
     * @param string $id migration ID
     * @return string
     * @throws \Exception
     */
    public function migrateTo(string $id): string
    {
        $manager = $this->getManager();
        $manager->migrate($this->getConfig()->getDefaultEnvironment(), $id);

        return $this->output->fetch();
    }

    /**
     * 指定バージョンをロールバックする
     *
     * @param string|int $id migration ID
     * @return string
     * @throws \Exception
     */
    public function rollback($id): string
    {
        $manager = $this->getManager();
        $manager->rollback($this->getConfig()->getDefaultEnvironment(), $id);

        return $this->output->fetch();
    }

    /**
     * シードを実行する
     *
     * @param string|null $seed seed name
     * @return string
     * @throws \Exception
     */
    public function seed(?string $seed = null): string
    {
        $manager = $this->getManager();
        $manager->seed($this->getConfig()->getDefaultEnvironment(), $seed);

        return $this->output->fetch();
    }

    /**
     * マイグレーションファイルの内容を取得する
     *
     * @param string $id migration ID
     * @return string
     * @throws \Cake\Http\Exception\NotFoundException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getFileContent(string $id): string
    {
        $migrations = $this->getManager()->getMigrations($this->getConfig()->getDefaultEnvironment());

        $matched = false;
        foreach ($migrations as $version => $migration) {
            if ($version === (int)$id) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            throw new NotFoundException(__d('elastic.migration_manager', 'Migration Not Found. ID: {0}', $id));
        }

        $reflection = new ReflectionClass($migration);

        return file_get_contents($reflection->getFileName());
    }

    /**
     * change connection
     *
     * @param string $connection target connection name
     * @return self
     */
    public function withConnection(string $connection): MigrationGroup
    {
        return new static($this->name, $connection);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'config' => $this->getConfig(),
            'migrations' => $this->getMigrations()->toList(),
        ];
    }
}
