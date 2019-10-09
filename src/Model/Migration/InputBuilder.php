<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

namespace Elastic\MigrationManager\Model\Migration;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Inputの作成
 */
class InputBuilder
{
    /**
     * @param array $args options
     * @return InputInterface
     */
    public function build($args = [])
    {
        return new ArrayInput($args, $this->getDefinition());
    }

    /**
     * @return InputDefinition
     */
    private function getDefinition()
    {
        static $definition;
        if ($definition !== null) {
            return $definition;
        }

        $definition = new InputDefinition();
        $definition->addOption(new InputOption('--plugin', 'p', InputOption::VALUE_REQUIRED));
        $definition->addOption(new InputOption('--connection', 'c', InputOption::VALUE_REQUIRED));
        $definition->addOption(new InputOption('--source', 's', InputOption::VALUE_REQUIRED));

        return $definition;
    }
}
