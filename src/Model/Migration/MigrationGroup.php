<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

namespace Elastic\MigrationManager\Model\Migration;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Elastic\MigrationManager\Model\Entity\MigrationStatus;
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
     */
    public function __construct($name)
    {
        $this->name = $name;

        $this->input = $this->buildInput($name);
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
     * @return InputInterface
     */
    private function buildInput($name)
    {
        $args = [];
        if ($name !== Configure::read('App.namespace')) {
            $args['--plugin'] = $name;
        }

        return (new InputBuilder())->build($args);
    }

    /**
     * マイグレーションリストの取得
     *
     * @return CollectionInterface|MigrationStatus[]
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
     */
    private function getManager()
    {
        if ($this->manager === null) {
            $this->output = new BufferedOutput();
            $this->manager = new MigrationManager($this->getConfig(), $this->buildInput($this->getName()), $this->output);
        }

        return $this->manager;
    }

    /**
     * 最終のマイグレーション
     *
     * @return MigrationStatus
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

        $refrection = new ReflectionClass($migration);

        return file_get_contents($refrection->getFileName());
    }

    /**
     * @return array
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
