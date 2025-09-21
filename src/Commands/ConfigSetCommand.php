<?php

namespace Marceloxp\Iartisan\Commands;

use Marceloxp\Iartisan\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigSetCommand extends Command
{
    protected static $defaultName = 'config:set';

    protected function configure(): void
    {
        $this
            ->setName('config:set')
            ->setDescription('Set global config key (ex: GEMINI_MODEL=gemini-2.5-flash)')
            ->addArgument('pair', InputArgument::REQUIRED, 'KEY=VALUE');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pair = $input->getArgument('pair');
        if (strpos($pair, '=') === false) {
            $output->writeln('<error>Invalid format. Use: iartisan config:set GEMINI_MODEL=gemini-2.5-flash</error>');
            return Command::FAILURE;
        }

        [$key, $value] = explode('=', $pair, 2);
        $key = trim($key);
        $value = trim($value);

        $map = [
            'GEMINI_MODEL' => 'gemini_model'
        ];

        $internal = $map[$key] ?? strtolower($key);

        $cfg = new ConfigManager();
        $cfg->set($internal, $value);

        $output->writeln("<info>Saved: {$key}</info>");
        return Command::SUCCESS;
    }
}
