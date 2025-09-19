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

    protected function configure()
    {
        $this->setDescription('Set global config key (ex: GEMINI_API_KEY=xxxx)')
            ->addArgument('pair', InputArgument::REQUIRED, 'KEY=VALUE');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pair = $input->getArgument('pair');
        if (strpos($pair, '=') === false) {
            $output->writeln('<error>Formato inválido. Use: iartisan config:set GEMINI_API_KEY=xxxx</error>');
            return Command::FAILURE;
        }

        [$key, $value] = explode('=', $pair, 2);
        $key = trim($key);
        $value = trim($value);

        $map = [
            'GEMINI_API_KEY' => 'gemini_api_key'
        ];

        $internal = $map[$key] ?? strtolower($key);

        $cfg = new ConfigManager();
        $cfg->set($internal, $value);

        $output->writeln("<info>Salvo: {$key}</info>");
        return Command::SUCCESS;
    }
}
