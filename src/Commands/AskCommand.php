<?php

namespace Marceloxp\Iartisan\Commands;

use Marceloxp\Iartisan\Config\ConfigManager;
use Marceloxp\Iartisan\Services\GeminiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

class AskCommand extends Command
{
    protected static $defaultName = 'ask';

    protected function configure()
    {
        $this->setDescription('Ask IArtisan for an artisan command (used internally)')
            ->addArgument('prompt', InputArgument::REQUIRED, 'Natural language prompt');
    }

    private function getGeminiApiKey(): ?string
    {
        // 1 - Variável de ambiente oficial
        if ($key = getenv('GEMINI_API_KEY')) {
            return $key;
        }

        // 2 - Variável de ambiente alternativa
        if ($key = getenv('IARTISAN_GEMINI_KEY')) {
            return $key;
        }

        // 3 - Config local (~/.iartisan/config.json)
        $config = new ConfigManager();
        if ($config->has('GEMINI_API_KEY')) {
            return $config->get('GEMINI_API_KEY');
        }

        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectRoot = getcwd();

        $prompt = $input->getArgument('prompt');

        $apiKey = $this->getGeminiApiKey();

        if (!$apiKey) {
            $output->writeln('<error>Chave de API não encontrada.</error>');
            $output->writeln('Use "export GEMINI_API_KEY=xxxx" no terminal ou "iartisan config:set GEMINI_API_KEY=xxxx".');
            return Command::FAILURE;
        }

        $client = new GeminiClient($apiKey);

        try {
            $command = $client->generate($prompt);
        } catch (\Throwable $e) {
            $output->writeln('<error>Erro ao acessar Gemini: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>Comando: </info>');
        $output->writeln($command);

        // Verifica se é um projeto Laravel (arquivo artisan presente)
        if (file_exists($projectRoot . '/artisan')) {
            $output->writeln('');
            if (!str_starts_with($command, 'php artisan')) {
                $output->writeln('<error>Comando inválido: apenas comandos "php artisan" são permitidos.</error>');
                return Command::FAILURE;
            }

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Executar? [yes] ', false, '/^(y|yes)/i');

            if ($helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Executando...</comment>');
                $process = Process::fromShellCommandline($command);
                $process->setTimeout(3600);
                $process->run(function ($type, $buffer) use ($output) {
                    $output->write($buffer);
                });

                $exit = $process->getExitCode();
                $output->writeln('');
                $output->writeln('<info>Comando finalizado com código: ' . $exit . '</info>');
                return $exit === 0 ? Command::SUCCESS : Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
