<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

namespace Elastic\MigrationManager\Test\TestCase\Model\Migration;

use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Cake\TestSuite\TestCase;
use Elastic\MigrationManager\Model\Entity\MigrationStatus;
use Elastic\MigrationManager\Model\Migration\MigrationGroup;
use Phinx\Config\Config;

/**
 * Class MigrationGroupTest
 */
class MigrationGroupTest extends TestCase
{
    /**
     * @var MigrationGroup
     */
    private $migrationManagerGroup;

    public function setUp()
    {
        parent::setUp();
        $this->migrationManagerGroup = new MigrationGroup('Elastic/MigrationManager');
        $this->migrationManagerGroup->rollback(0);
    }

    public function tearDown()
    {
        $this->migrationManagerGroup->rollback(0);
        unset($this->migrationManagerGroup);
        parent::tearDown();
    }

    /**
     * メインアプリケーションのマイグレーションを取得できる
     */
    public function testConstructApp()
    {
        $object = new MigrationGroup(Configure::read('App.namespace'));

        $this->assertSame('App', $object->getName());
        $this->assertInstanceOf(Config::class, $object->getConfig());
        $this->assertSame('default', $object->getConfig()->getDefaultEnvironment());
        // Migrations plugin >= 2.1 以降 CONFIG が使用されるため環境によりマイグレーションパスが異なる
        $configPathMatch = preg_quote(ROOT, '!') . '(/tests/test_app)?/config';

        $this->assertRegExp('!^' . $configPathMatch . '/Migrations$!', $object->getConfig()->getMigrationPaths()[0]);
        $this->assertRegExp('!^' . $configPathMatch . '/Seeds$!', $object->getConfig()->getSeedPaths()[0]);
        $environment = $object->getConfig()->getEnvironment('default');
        $this->assertSame('phinxlog', $environment['default_migration_table']);
    }

    /**
     * プラグインのマイグレーションを取得できる
     */
    public function testConstructPlugin()
    {
        $object = new MigrationGroup('Elastic/MigrationManager');

        $this->assertSame('Elastic/MigrationManager', $object->getName());
        $this->assertInstanceOf(Config::class, $object->getConfig());
        $this->assertSame('default', $object->getConfig()->getDefaultEnvironment());
        $this->assertSame([
            Plugin::configPath('Elastic/MigrationManager') . 'Migrations',
        ], $object->getConfig()->getMigrationPaths());
        $this->assertSame([
            Plugin::configPath('Elastic/MigrationManager') . 'Seeds',
        ], $object->getConfig()->getSeedPaths());
        $environment = $object->getConfig()->getEnvironment('default');
        $this->assertSame('elastic_migration_manager_phinxlog', $environment['default_migration_table']);
    }

    /**
     * マイグレーションのリストを取得できる
     */
    public function testGetMigrations()
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $this->assertInstanceOf(CollectionInterface::class, $migrations);
        $first = $migrations->first();
        $this->assertInstanceOf(MigrationStatus::class, $first);
        $this->assertSame('down', $first->status);
        $this->assertSame('20191008091658', $first->id);
        $this->assertSame('InitForTest', $first->name);
    }

    /**
     * 最後のマイグレーションが取得できる
     */
    public function testGetLastMigration()
    {
        $last = $this->migrationManagerGroup->getLastMigration();

        $this->assertInstanceOf(MigrationStatus::class, $last);
        $this->assertSame('down', $last->status);
        $this->assertSame('20191008091959', $last->id);
        $this->assertSame('ThirdMigrationForTest', $last->name);
    }

    /**
     * 指定のバージョンへマイグレーションを実行できる
     */
    public function testMigrateTo()
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $first = $migrations->first();
        $this->assertSame('down', $first->status);

        $result = $this->migrationManagerGroup->migrateTo($first->id);

        $this->assertContains('20191008091658 InitForTest: migrated', $result);

        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'down',
            'ThirdMigrationForTest' => 'down',
        ], $statuses->toArray());
    }

    /**
     * 指定のバージョンをロールバックできる
     */
    public function testRollback()
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $first = $migrations->first();
        $last = $migrations->last();
        $this->migrationManagerGroup->migrateTo($last->id);
        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'up',
            'ThirdMigrationForTest' => 'up',
        ], $statuses->toArray());

        $result = $this->migrationManagerGroup->rollback($first->id);

        $this->assertContains('20191008091959 ThirdMigrationForTest: reverted', $result);
        $this->assertContains('20191008091715 SecondMigrationForTest: reverted', $result);

        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'down',
            'ThirdMigrationForTest' => 'down',
        ], $statuses->toArray());
    }

    /**
     * マイグレーションを全てロールバックできる
     */
    public function testRollbackAll()
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $last = $migrations->last();
        $this->migrationManagerGroup->migrateTo($last->id);
        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'up',
            'ThirdMigrationForTest' => 'up',
        ], $statuses->toArray());

        $result = $this->migrationManagerGroup->rollback(0);

        $this->assertContains('20191008091658 InitForTest: reverted', $result);
        $this->assertContains('20191008091959 ThirdMigrationForTest: reverted', $result);
        $this->assertContains('20191008091715 SecondMigrationForTest: reverted', $result);

        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'down',
            'SecondMigrationForTest' => 'down',
            'ThirdMigrationForTest' => 'down',
        ], $statuses->toArray());
    }

    /**
     * 指定IDのマイグレーションファイルの内容を取得できる
     */
    public function testGetFileContent()
    {
        $expects = file_get_contents(Plugin::configPath('Elastic/MigrationManager') . 'Migrations/20191008091658_InitForTest.php');
        $result = $this->migrationManagerGroup->getFileContent('20191008091658');

        $this->assertSame($expects, $result);
    }

    /**
     * 指定IDのマイグレーションファイルが存在しない場合はNotFoundExceptionを返す
     */
    public function testGetFileContentNotExists()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Migration Not Found. ID: 20110102030405');

        $this->migrationManagerGroup->getFileContent('20110102030405');
    }
}
