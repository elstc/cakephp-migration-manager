<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */
declare(strict_types=1);

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
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function build(array $args = []): InputInterface
    {
        return new ArrayInput($args, $this->getDefinition());
    }

    /**
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    private function getDefinition(): InputDefinition
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
