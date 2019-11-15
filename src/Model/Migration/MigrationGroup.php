<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

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
use ReflectionException;
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
     * @var Manager
     */
    private $manager;

    /**
     * @var BufferedOutput
     */
    private $output;

    /**
     * MigrationGroup constructor.
     *
     * @param string $name the app / plugin name
     * @param string|null $connection the connection name
     */
    public function __construct($name, $connection = null)
    {
        $this->name = $name;

        $this->input = $this->buildInput($name, $connection);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Inputオブジェクトの構築
     *
     * @param string $name the app / plugin name
     * @param string|null $connection the connection name
     * @return InputInterface
     */
    private function buildInput($name, $connection = null)
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
     * @return CollectionInterface|MigrationStatus[]
     * @throws \Exception
     */
    public function getMigrations()
    {
        $manager = $this->getManager();
        $statuses = $manager->printStatus($this->getConfig()->getDefaultEnvironment(), 'json');
        $migrations = array_map(static function ($status) {
            return new MigrationStatus($status);
        }, $statuses);

        return new Collection($migrations);
    }

    /**
     * @return Manager|MigrationManager
     * @throws \Exception
     */
    private function getManager()
    {
        if ($this->manager === null) {
            $this->output = new BufferedOutput();
            $this->manager = new MigrationManager($this->getConfig(), $this->input, $this->output);
            $this->setAdapter();
        }

        return $this->manager;
    }

    /**
     * Sets the adapter the manager is going to need to operate on the DB
     * This will make sure the adapter instance is a \Migrations\CakeAdapter instance
     *
     * @return void
     * @throws \Exception
     */
    private function setAdapter()
    {
        $env = $this->manager->getEnvironment('default');
        $adapter = $env->getAdapter();

        if ($adapter instanceof CakeAdapter) {
            return;
        }

        $connectionName = 'default';
        if ($this->input !== null && $this->input->getOption('connection')) {
            $connectionName = $this->input->getOption('connection');
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
     * @return MigrationStatus
     * @throws \Exception
     */
    public function getLastMigration()
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
    public function migrateTo($id)
    {
        $manager = $this->getManager();
        $manager->migrate($this->getConfig()->getDefaultEnvironment(), $id);

        return $this->output->fetch();
    }

    /**
     * 指定バージョンをロールバックする
     *
     * @param string $id migration ID
     * @return string
     * @throws \Exception
     */
    public function rollback($id)
    {
        $manager = $this->getManager();
        $manager->rollback($this->getConfig()->getDefaultEnvironment(), $id);

        return $this->output->fetch();
    }

    /**
     * マイグレーションファイルの内容を取得する
     *
     * @param string $id migration ID
     * @return string
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws \Exception
     */
    public function getFileContent($id)
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
     * @return $this
     */
    public function withConnection($connection)
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
