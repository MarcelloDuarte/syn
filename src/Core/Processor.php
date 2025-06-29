<?php

declare(strict_types=1);

namespace Syn\Core;

use Syn\Parser\Parser;
use Syn\Macro\MacroLoader;
use Syn\Transformer\Transformer;
use Syn\Plugin\PluginManager;
use Symfony\Component\Finder\Finder;

class Processor
{
    private Configuration $config;
    private Parser $parser;
    private MacroLoader $macroLoader;
    private Transformer $transformer;
    private PluginManager $pluginManager;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->parser = new Parser();
        $this->macroLoader = new MacroLoader();
        $this->transformer = new Transformer($config, $this->macroLoader);
        $this->pluginManager = new PluginManager();
        
        $this->initializePlugins();
        $this->loadMacros();
    }

    public function process(string $input, string $output): array
    {
        $results = [];
        
        if (is_file($input)) {
            $results[] = $this->processFile($input, $output);
        } else {
            $results = $this->processDirectory($input, $output);
        }
        
        return $results;
    }

    private function processFile(string $inputFile, string $outputPath): array
    {
        if (!file_exists($inputFile)) {
            throw new \InvalidArgumentException("Input file not found: {$inputFile}");
        }

        $content = file_get_contents($inputFile);
        if ($content === false) {
            throw new \RuntimeException("Could not read input file: {$inputFile}");
        }

        $processedContent = $this->transformer->transform($content, $inputFile);
        
        $outputFile = is_dir($outputPath) 
            ? $outputPath . '/' . basename($inputFile, '.syn.php') . '.php'
            : $outputPath;

        $this->ensureDirectoryExists(dirname($outputFile));
        file_put_contents($outputFile, $processedContent);

        return [
            'file' => $inputFile,
            'status' => 'success',
            'lines' => substr_count($content, "\n") + 1,
            'output' => $outputFile
        ];
    }

    private function processDirectory(string $inputDir, string $outputDir): array
    {
        if (!is_dir($inputDir)) {
            throw new \InvalidArgumentException("Input directory not found: {$inputDir}");
        }

        $this->ensureDirectoryExists($outputDir);
        
        $finder = new Finder();
        $finder->files()
            ->in($inputDir)
            ->name(['*.syn.php', '*.syn']);

        $results = [];
        
        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $outputFile = $outputDir . '/' . str_replace(['.syn.php', '.syn'], '.php', $relativePath);
            
            $this->ensureDirectoryExists(dirname($outputFile));
            
            $content = $file->getContents();
            $processedContent = $this->transformer->transform($content, $file->getPathname());
            
            file_put_contents($outputFile, $processedContent);
            
            $results[] = [
                'file' => $file->getPathname(),
                'status' => 'success',
                'lines' => substr_count($content, "\n") + 1,
                'output' => $outputFile
            ];
        }
        
        return $results;
    }

    private function initializePlugins(): void
    {
        foreach ($this->config->getPlugins() as $pluginClass) {
            $this->pluginManager->registerPlugin($pluginClass);
        }
    }

    private function loadMacros(): void
    {
        // Load macros from directories
        foreach ($this->config->getMacroDirectories() as $directory) {
            if (is_dir($directory)) {
                $this->macroLoader->loadFromDirectory($directory);
            }
        }
        
        // Load specific macro files
        foreach ($this->config->getMacroFiles() as $file) {
            if (file_exists($file)) {
                $this->macroLoader->loadFromFile($file);
            }
        }
        
        // Load macros from plugins
        $pluginMacros = $this->pluginManager->getAllMacros();
        foreach ($pluginMacros as $macro) {
            $this->macroLoader->addMacro($macro);
        }
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \RuntimeException("Could not create directory: {$directory}");
            }
        }
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    public function getMacroLoader(): MacroLoader
    {
        return $this->macroLoader;
    }

    public function getTransformer(): Transformer
    {
        return $this->transformer;
    }
} 
