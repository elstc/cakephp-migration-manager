<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

use Cake\View\View;
use Elastic\MigrationManager\Model\Migration\MigrationGroup;

/* @var View $this */
/* @var MigrationGroup $migrationGroup */
/* @var bool $canRollback */

$this->Html->meta('robots', 'noindex,nofollow', ['block' => true]);
?>
<h1><?= __d('elastic.migration_manager', '{0} Migrations', $migrationGroup->getName()) ?></h1>
<nav class="columns">
    <ul class="no-bullet">
        <li>
            <?= $this->Html->link(__d('elastic.migration_manager', 'Return to List'), ['action' => 'index']) ?>
        </li>
    </ul>
</nav>
<table class="table">
    <thead>
    <tr>
        <th style="width: 15%"><?= __d('elastic.migration_manager', 'Last Migration ID') ?></th>
        <th style="width: 5%"><?= __d('elastic.migration_manager', 'Status') ?></th>
        <th><?= __d('elastic.migration_manager', 'Name') ?></th>
        <th style="width: 30%;"><?= __d('elastic.migration_manager', 'Actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($migrationGroup->getMigrations() as $idx => $migrationStatus) : ?>
        <tr>
            <td><?= h($migrationStatus->id) ?></td>
            <td><?= h($migrationStatus->status) ?></td>
            <td>
                <?=
                $this->Html->link(
                    $migrationStatus->name,
                    [
                        'action' => 'showFile',
                        '?' => [
                            'groupName' => $migrationGroup->getName(),
                            'id' => $migrationStatus->id,
                        ],
                    ]
                );
                ?>
            </td>
            <td>
                <?php if ($migrationStatus->status === 'down') : ?>
                    <?=
                    $this->Form->postLink(
                        __d('elastic.migration_manager', 'Apply to here'),
                        ['action' => 'migrate'],
                        [
                            'data' => [
                                'groupName' => $migrationGroup->getName(),
                                'id' => $migrationStatus->id,
                            ],
                            'class' => 'button',
                            'style' => 'margin-bottom: 0;',
                            'confirm' => __d('elastic.migration_manager', 'Are you sure you want migration to: {0} {1}', $migrationStatus->id, $migrationStatus->name),
                        ]
                    )
                    ?>
                <?php endif; ?>
                <?php if ($canRollback && $migrationStatus->status === 'up') : ?>
                    <?=
                    $this->Form->postLink(
                        __d('elastic.migration_manager', 'Rollback to here'),
                        ['action' => 'rollback'],
                        [
                            'data' => [
                                'groupName' => $migrationGroup->getName(),
                                'id' => $migrationStatus->id,
                            ],
                            'class' => 'button small btn btn-danger',
                            'style' => 'margin-bottom: 0;',
                            'confirm' => __d('elastic.migration_manager', 'Are you sure you want rollback to: {0} {1}', $migrationStatus->id, $migrationStatus->name),
                        ]
                    )
                    ?>
                <?php endif; ?>
                <?php if ($canRollback && $idx === 0 && $migrationStatus->status === 'up') : ?>
                    <?=
                    $this->Form->postLink(
                        __d('elastic.migration_manager', 'Rollback This'),
                        ['action' => 'rollback'],
                        [
                            'data' => [
                                'groupName' => $migrationGroup->getName(),
                                'id' => 0,
                            ],
                            'class' => 'button small btn btn-danger',
                            'style' => 'margin-bottom: 0;',
                            'confirm' => __d('elastic.migration_manager', 'Are you sure you want rollback all'),
                        ]
                    )
                    ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
