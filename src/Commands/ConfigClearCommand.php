<?php

namespace Marceloxp\Iartisan\Commands;

use Marceloxp\Iartisan\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigClearCommand extends Command
{
    protected static $defaultName = 'config:clear';

    protected function configure(): void
    {
        $this
            ->setDescription('Remove a saved configuration')
            ->addArgument('key', InputArgument::REQUIRED, 'Name of the key to remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');
        $config = new ConfigManager();

        if ($config->has($key)) {
            $config->remove($key);
            $output->writeln("<info>Removed: {$key}</info>");
        } else {
            $output->writeln("<comment>Key {$key} not found.</comment>");
        }

        return Command::SUCCESS;
    }
}
