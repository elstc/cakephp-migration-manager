<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

use Elastic\MigrationManager\Model\Migration\MigrationGroup;

/* @var $migrationGroups MigrationGroup[] */

$this->Html->meta('robots', 'noindex,nofollow', ['block' => true]);
?>
<table class="table">
    <thead>
    <tr>
        <th style="width: 20%;"><?= __d('elastic.migration_manager', 'Application / Plugin Name') ?></th>
        <th style="width: 15%"><?= __d('elastic.migration_manager', 'Last Migration ID') ?></th>
        <th style="width: 5%"><?= __d('elastic.migration_manager', 'Status') ?></th>
        <th><?= __d('elastic.migration_manager', 'Name') ?></th>
        <th style="width: 20%;"></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($migrationGroups as $migrationGroup) : $lastMigration = $migrationGroup->getLastMigration(); ?>
        <tr>
            <td><?= h($migrationGroup->getName()) ?></td>
            <?php if ($lastMigration) : ?>
                <td><?= h($lastMigration->id) ?></td>
                <td><?= h($lastMigration->status) ?></td>
                <td><?= h($lastMigration->name) ?></td>
                <td class="actions">
                    <?=
                    $this->Html->link(
                        __d('elastic.migration_manager', 'Manage'),
                        ['action' => 'view', '?' => ['name' => $migrationGroup->getName()]]
                    )
                    ?>
                </td>
            <?php else: ?>
                <td></td>
                <td></td>
                <td></td>
                <td class="actions"></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
