<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Test\TestCase\Model\Migration;

use Cake\Collection\CollectionInterface;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Elastic\MigrationManager\Model\Migration\MigrationGroup;
use Elastic\MigrationManager\Model\Migration\MigrationGroups;
use Traversable;

/**
 * Class MigrationGroupsTest
 */
class MigrationGroupsTest extends TestCase
{
    /**
     * @var MigrationGroups
     */
    private $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new MigrationGroups();
    }

    public function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    /**
     * マイグレーションの存在するアプリケーションとプラグインのリストを取得できる
     */
    public function testFetchAll(): void
    {
        $results = $this->subject->fetchAll();

        $this->assertInstanceOf(Traversable::class, $results);
        $this->assertInstanceOf(CollectionInterface::class, $results);

        $first = $results->first();
        $this->assertInstanceOf(MigrationGroup::class, $first);
        $this->assertSame('App', $first->getName());

        $names = $results->map(static function (MigrationGroup $group) {
            return $group->getName();
        });
        $this->assertContains('Elastic/MigrationManager', $names->toList());
    }

    /**
     * コネクションを指定できる
     */
    public function testWithConnection(): void
    {
        $groups = $this->subject->withConnection('other');

        $results = $groups->fetchAll();

        $first = $results->first();
        $this->assertSame('default', $first->getConfig()->getDefaultEnvironment());
        $this->assertSame(5432, $first->getConfig()->getEnvironment('default')['port']);
    }
}
