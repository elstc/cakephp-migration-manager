<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

namespace Elastic\MigrationManager\Model\Migration;

use Migrations\CakeManager;
use Phinx\Util\Util;

/**
 * MigrationManagerのラッパー
 */
class MigrationManager extends CakeManager
{
    /**
     * Prints the specified environment's migration status.
     *
     * NOTE: Only output array
     *
     * @param string $environment Environment name.
     * @param null|string $format Format (`json` or `array`).
     * @return array[] Array of migrations or json string.
     * @see \Migrations\CakeManager::printStatus()
     */
    public function printStatus($environment, $format = null): array
    {
        $migrations = [];
        $phpFiles = $this->getMigrationFiles();
        if (count($phpFiles)) {
            $env = $this->getEnvironment($environment);
            $versions = $env->getVersionLog();

            foreach ($phpFiles as $filePath) {
                if (!Util::isValidMigrationFileName(basename($filePath))) {
                    continue;
                }
                $version = Util::getVersionFromFileName(basename($filePath));
                $name = Util::mapFileNameToClassName(basename($filePath));

                if (array_key_exists($version, $versions)) {
                    $status = 'up';
                    unset($versions[$version]);
                } else {
                    $status = 'down';
                }

                $migrationParams = [
                    'status' => $status,
                    'id' => $version,
                    'name' => $name,
                    'missing' => false,
                ];

                $migrations[$version] = $migrationParams;
            }

            foreach ($versions as $missing) {
                $version = $missing['version'];
                $migrationParams = [
                    'status' => 'up',
                    'id' => $version,
                    'name' => $missing['migration_name'],
                    'missing' => true,
                ];

                $migrations[$version] = $migrationParams;
            }
        }

        ksort($migrations);

        return array_values($migrations);
    }
}
