<?php

declare(strict_types=1);

namespace Syn\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Syn\Core\Processor;
use Syn\Core\Configuration;

class SyncCommand extends Command
{
    protected static $defaultName = 'sync';
    protected static $defaultDescription = 'Process .syn and .syn.php files with macro definitions';

    protected function configure(): void
    {
        $this
           ->setName('sync')
            ->setDescription('Process .syn and .syn.php files with macro definitions')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Input file or directory to process'
            )
            ->addOption(
                'out',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file or directory'
            )
            ->addOption(
                'macro-dir',
                'm',
                InputOption::VALUE_REQUIRED,
                'Directory containing macro definitions (.syn files)'
            )
            ->addOption(
                'macro-file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Specific macro file to use'
            )
            ->addOption(
                'preserve-line-numbers',
                'p',
                InputOption::VALUE_NONE,
                'Preserve line numbers in output (for debugging)'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Configuration file path'
            )
            ->setHelp(<<<'HELP'
The <info>sync</info> command processes PHP files with custom syntax using macro definitions.

Examples:
  <info>sync input.syn.php --out=output.php</info>
  <info>sync src/ --macro-dir=macros/ --out=compiled/</info>
  <info>sync src/ --macro-file=my-macros.syn --out=compiled/ --verbose</info>

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Syn PHP Preprocessor');

        try {
            $config = $this->buildConfiguration($input);
            $processor = new Processor($config);
            
            $inputPath = $input->getArgument('input');
            $outputPath = $input->getOption('out');
            
            if (!$outputPath) {
                $io->error('Output path is required. Use --out option.');
                return Command::FAILURE;
            }

            $result = $processor->process($inputPath, $outputPath);
            
            if ($input->getOption('verbose')) {
                $io->section('Processing Results');
                $io->table(
                    ['File', 'Status', 'Lines'],
                    array_map(fn($r) => [$r['file'], $r['status'], $r['lines']], $result)
                );
            }

            $io->success(sprintf('Processed %d files successfully', count($result)));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error($e->getMessage());
            
            if ($input->getOption('verbose')) {
                $io->writeln($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }

    private function buildConfiguration(InputInterface $input): Configuration
    {
        $config = new Configuration();
        
        if ($macroDir = $input->getOption('macro-dir')) {
            $config->setMacroDirectories([$macroDir]);
        }
        
        if ($macroFile = $input->getOption('macro-file')) {
            $config->setMacroFiles([$macroFile]);
        }
        
        if ($configFile = $input->getOption('config')) {
            $config->loadFromFile($configFile);
        }
        
        $config->setPreserveLineNumbers($input->getOption('preserve-line-numbers'));
        $config->setVerbose($input->getOption('verbose'));
        
        return $config;
    }
} 
