<?php

namespace Marceloxp\Iartisan\Commands;

use Marceloxp\Iartisan\Services\GeminiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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
            ->addArgument('prompt', InputArgument::REQUIRED, 'Natural language prompt')
            ->addOption('filament', 'f', InputOption::VALUE_OPTIONAL, 'Filament version (3 or 4)', null)
            ->addOption('filament3', null, InputOption::VALUE_NONE, 'Use Filament version 3')
            ->addOption('filament4', null, InputOption::VALUE_NONE, 'Use Filament version 4');
    }

    private function getGeminiApiKey(): ?string
    {
        // 1 - Official environment variable
        if ($key = getenv('GEMINI_API_KEY')) {
            return $key;
        }

        // 2 - Alternative environment variable
        if ($key = getenv('IARTISAN_GEMINI_KEY')) {
            return $key;
        }

        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectRoot = getcwd();
        $prompt = $input->getArgument('prompt');
        $filamentVersion = $input->hasOption('filament3') && $input->getOption('filament3') ? '3' : ($input->hasOption('filament4') && $input->getOption('filament4') ? '4' : $input->getOption('filament'));

        if (!$prompt) {
            $output->writeln('<error>No prompt provided. Please provide a natural language prompt.</error>');
            return Command::FAILURE;
        }

        $apiKey = $this->getGeminiApiKey();

        if (!$apiKey) {
            $output->writeln('<error>API key not found.</error>');
            $output->writeln('Set it using "export GEMINI_API_KEY=xxxx" in your terminal.');
            return Command::FAILURE;
        }

        $client = new GeminiClient($apiKey);

        try {
            $command = $client->generate($prompt, $filamentVersion);
        } catch (\Throwable $e) {
            $output->writeln('<error>Error accessing Gemini: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        // Join multi-line commands into a single line
        $command = implode(' ', array_filter(array_map('trim', explode("\n", $command))));

        // Enhanced visual feedback
        $output->writeln('');
        $output->writeln('<fg=cyan;options=bold>Generated Command:</>');
        $output->writeln('<fg=green>' . $command . '</>');

        // Check if it's a Laravel project (artisan file exists)
        if (file_exists($projectRoot . '/artisan')) {
            // Sanitize command: ensure it starts with 'php artisan'
            if (!str_starts_with(strtolower($command), 'php artisan')) {
                $output->writeln('<error>Invalid command: only "php artisan" commands are allowed.</error>');
                return Command::FAILURE;
            }

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Execute command? [yes] ', false, '/^(y|yes)/i');

            if ($helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Executing...</comment>');
                $process = Process::fromShellCommandline($command);
                $process->setTimeout(3600);
                $process->run(function ($type, $buffer) use ($output) {
                    $output->write($buffer);
                });

                $exit = $process->getExitCode();
                $output->writeln('');
                $output->writeln('<info>Command finished with exit code: ' . $exit . '</info>');
                return $exit === 0 ? Command::SUCCESS : Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
