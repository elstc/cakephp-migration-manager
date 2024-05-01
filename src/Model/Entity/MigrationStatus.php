<?php
/*
 * Copyright 2024 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Model\Entity;

use Cake\ORM\Entity;

/**
 * マイグレーション
 *
 * @property string $status
 * @property string|int $id
 * @property string $name
 * @property bool $missing
 */
class MigrationStatus extends Entity
{
}
