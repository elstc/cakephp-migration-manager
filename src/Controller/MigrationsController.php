<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;
use Elastic\MigrationManager\Model\Migration\MigrationGroup;
use Elastic\MigrationManager\Model\Migration\MigrationGroups;

/**
 * Migrations Controller
 *
 * @property \Cake\Controller\Component\FlashComponent|null $Flash
 * @property \Authorization\Controller\Component\AuthorizationComponent|null $Authorization
 */
class MigrationsController extends BaseController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        if (!$this->components()->has('Flash')) {
            $this->loadComponent('Flash');
        }
        if ($this->components()->has('Authorization')) {
            $authorization = $this->components()->get('Authorization');
            $authorization->setConfig('authorizeModel', [
                'migrate',
                'rollback',
                'showFile',
            ]);
            $authorization->setConfig('actionMap', [
                'migrate' => 'edit',
                'rollback' => 'edit',
                'showFile' => 'view',
            ]);
        }
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index(): void
    {
        $migrationGroups = (new MigrationGroups())->fetchAll();

        $this->set(compact('migrationGroups'));
    }

    /**
     * Show MigrationGroup
     *
     * @return void
     */
    public function view(): void
    {
        $migrationGroup = new MigrationGroup($this->getRequest()->getQuery('name'));
        $canRollback = Configure::read('Elastic/MigrationManager.canRollback', false);

        $this->set(compact('migrationGroup', 'canRollback'));
    }

    /**
     * Migrate to Target ID
     *
     * @return \Cake\Http\Response
     */
    public function migrate(): Response
    {
        $this->getRequest()->allowMethod(['post']);

        $groupName = $this->getRequest()->getData('groupName');
        $id = $this->getRequest()->getData('id');
        if (empty($groupName) || empty($id)) {
            $this->Flash->error(__d('elastic.migration_manager', 'Missing required arguments.'));

            return $this->redirect(['action' => 'index']);
        }

        $migrationGroup = new MigrationGroup($groupName);
        $result = $migrationGroup->migrateTo($id);

        $this->Flash->success(__d('elastic.migration_manager', 'Migration success: {0}', nl2br(h($result))), [
            'escape' => false,
        ]);

        return $this->redirect(['action' => 'view', '?' => ['name' => $groupName]]);
    }

    /**
     * Rollback to Target ID
     *
     * @return \Cake\Http\Response
     */
    public function rollback(): Response
    {
        $this->getRequest()->allowMethod(['post']);

        if (!Configure::read('Elastic/MigrationManager.canRollback', false)) {
            $this->Flash->error(__d('elastic.migration_manager', 'Can not rollback.'));

            return $this->redirect(['action' => 'index']);
        }

        $groupName = $this->getRequest()->getData('groupName');
        $id = $this->getRequest()->getData('id');
        if (empty($groupName) || $id === '' || $id === null) {
            $this->Flash->error(__d('elastic.migration_manager', 'Missing required arguments.'));

            return $this->redirect(['action' => 'index']);
        }

        $migrationGroup = new MigrationGroup($groupName);
        $result = $migrationGroup->migrateTo($id);

        $this->Flash->success(__d('elastic.migration_manager', 'Rollback success: {0}', nl2br(h($result))), [
            'escape' => false,
        ]);

        return $this->redirect(['action' => 'view', '?' => ['name' => $groupName]]);
    }

    /**
     * Show specific migration file
     *
     * @return void
     */
    public function showFile(): void
    {
        $migrationGroup = new MigrationGroup($this->getRequest()->getQuery('groupName'));
        $migration = $migrationGroup->getMigrations()->firstMatch(['id' => $this->getRequest()->getQuery('id')]);
        $fileContent = $migrationGroup->getFileContent($migration->id);

        $this->set(compact('migrationGroup', 'migration', 'fileContent'));
    }
}
