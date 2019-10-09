<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

namespace Elastic\MigrationManager\Test\TestCase\Model\Migration;

use Cake\Collection\CollectionInterface;
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
     * @var \Elastic\MigrationManager\Model\Migration\MigrationGroups
     */
    private $subject;

    public function setUp()
    {
        parent::setUp();
        $this->subject = new \Elastic\MigrationManager\Model\Migration\MigrationGroups();
    }

    public function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * マイグレーションの存在するアプリケーションとプラグインのリストを取得できる
     */
    public function testFetchAll()
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
}
