<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

namespace Elastic\MigrationManager\Model\Entity;

use Cake\ORM\Entity;

/**
 * マイグレーション
 *
 * @property string $status
 * @property string $id
 * @property string $name
 * @property bool $missing
 */
class MigrationStatus extends Entity
{
}
