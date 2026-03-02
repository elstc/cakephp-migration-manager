<?php
/*
 * Copyright 2026 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Test\TestCase\Model\Migration;

use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Exception\NotFoundException;
use Cake\TestSuite\TestCase;
use Elastic\MigrationManager\Model\Entity\MigrationStatus;
use Elastic\MigrationManager\Model\Migration\MigrationGroup;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class MigrationGroupTest
 */
#[CoversClass(MigrationGroup::class)]
class MigrationGroupTest extends TestCase
{
    /**
     * @var MigrationGroup
     */
    private MigrationGroup $migrationManagerGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->migrationManagerGroup = new MigrationGroup('Elastic/MigrationManager');
        $this->migrationManagerGroup->rollback(0);
    }

    public function tearDown(): void
    {
        $this->migrationManagerGroup->rollback(0);
        unset($this->migrationManagerGroup);

        parent::tearDown();
    }

    /**
     * メインアプリケーションのマイグレーションを取得できる
     */
    public function testConstructApp(): void
    {
        $object = new MigrationGroup(Configure::read('App.namespace'));

        $this->assertSame('App', $object->getName());
        $this->assertInstanceOf(CollectionInterface::class, $object->getMigrations());
    }

    /**
     * プラグインのマイグレーションを取得できる
     */
    public function testConstructPlugin(): void
    {
        $object = new MigrationGroup('Elastic/MigrationManager');

        $this->assertSame('Elastic/MigrationManager', $object->getName());
        $this->assertInstanceOf(CollectionInterface::class, $object->getMigrations());
    }

    /**
     * マイグレーションのリストを取得できる
     */
    public function testGetMigrations(): void
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $this->assertInstanceOf(CollectionInterface::class, $migrations);
        $first = $migrations->first();
        $this->assertInstanceOf(MigrationStatus::class, $first);
        $this->assertSame('down', $first->status);
        $this->assertSame('20191008091658', (string)$first->id);
        $this->assertSame('InitForTest', $first->name);
    }

    /**
     * 最後のマイグレーションが取得できる
     */
    public function testGetLastMigration(): void
    {
        $last = $this->migrationManagerGroup->getLastMigration();

        $this->assertInstanceOf(MigrationStatus::class, $last);
        $this->assertSame('down', $last->status);
        $this->assertSame('20191008092000', (string)$last->id);
        $this->assertSame('FourthMigrationForTest', $last->name);
    }

    /**
     * 指定のバージョンへマイグレーションを実行できる
     */
    public function testMigrateTo(): void
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $first = $migrations->first();
        $this->assertSame('down', $first->status);

        $this->assertTrue($this->migrationManagerGroup->migrateTo($first->id));

        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'down',
            'ThirdMigrationForTest' => 'down',
            'FourthMigrationForTest' => 'down',
        ], $statuses->toArray());
    }

    /**
     * 指定のバージョンをロールバックできる
     */
    public function testRollback(): void
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $first = $migrations->first();
        $last = $migrations->last();
        $this->assertTrue($this->migrationManagerGroup->migrateTo($last->id));
        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'up',
            'ThirdMigrationForTest' => 'up',
            'FourthMigrationForTest' => 'up',
        ], $statuses->toArray());

        $this->assertTrue($this->migrationManagerGroup->rollback($first->id));

        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'down',
            'ThirdMigrationForTest' => 'down',
            'FourthMigrationForTest' => 'down',
        ], $statuses->toArray());
    }

    /**
     * マイグレーションを全てロールバックできる
     */
    public function testRollbackAll(): void
    {
        $migrations = $this->migrationManagerGroup->getMigrations();

        $last = $migrations->last();
        $this->assertTrue($this->migrationManagerGroup->migrateTo($last->id));
        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'up',
            'SecondMigrationForTest' => 'up',
            'ThirdMigrationForTest' => 'up',
            'FourthMigrationForTest' => 'up',
        ], $statuses->toArray());

        $this->assertTrue($this->migrationManagerGroup->rollback(0));

        $statuses = $this->migrationManagerGroup->getMigrations()->combine('name', 'status');
        $this->assertSame([
            'InitForTest' => 'down',
            'SecondMigrationForTest' => 'down',
            'ThirdMigrationForTest' => 'down',
            'FourthMigrationForTest' => 'down',
        ], $statuses->toArray());
    }

    /**
     * 指定IDのマイグレーションファイルの内容を取得できる
     */
    public function testGetFileContent(): void
    {
        $expects = file_get_contents(Plugin::configPath('Elastic/MigrationManager') . 'Migrations/20191008091658_InitForTest.php');
        $result = $this->migrationManagerGroup->getFileContent('20191008091658');

        $this->assertSame($expects, $result);
    }

    /**
     * BaseMigrationベースのマイグレーションファイルの内容を取得できる
     */
    public function testGetFileContentBaseMigration(): void
    {
        // Arrange
        // -----------------------------------------------
        // BaseMigration を使ったマイグレーションファイルの期待値を準備する
        $expects = file_get_contents(Plugin::configPath('Elastic/MigrationManager') . 'Migrations/20191008092000_FourthMigrationForTest.php');

        // Act
        // -----------------------------------------------
        // BaseMigration ベースのマイグレーションファイル内容を取得する
        $result = $this->migrationManagerGroup->getFileContent('20191008092000');

        // Assert
        // -----------------------------------------------
        // ファイル内容が正しいことを検証する
        $this->assertSame($expects, $result);
    }

    /**
     * 指定IDのマイグレーションファイルが存在しない場合はNotFoundExceptionを返す
     */
    public function testGetFileContentNotExists(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Migration Not Found. ID: 20110102030405');

        $this->migrationManagerGroup->getFileContent('20110102030405');
    }

    /**
     * 接続を指定して初期化できる
     */
    public function testConstructWithConnection(): void
    {
        $migrationGroup = new MigrationGroup('Elastic/MigrationManager', 'other');

        $this->assertInstanceOf(CollectionInterface::class, $migrationGroup->getMigrations());
    }

    /**
     * 接続を指定できる
     */
    public function testWithConnection(): void
    {
        $migrationGroup = new MigrationGroup('Elastic/MigrationManager');
        $withConnection = $migrationGroup->withConnection('other');

        $this->assertInstanceOf(CollectionInterface::class, $withConnection->getMigrations());

        // immutable: 元のオブジェクトは変更されていないことを確認する
        $this->assertSame('Elastic/MigrationManager', $migrationGroup->getName());
    }
}
