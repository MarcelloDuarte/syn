<?php

declare(strict_types=1);

namespace Syn\Core;

class Configuration
{
    private array $macroDirectories = [];
    private array $macroFiles = [];
    private bool $preserveLineNumbers = false;
    private bool $verbose = false;
    private array $plugins = [];
    private array $customSettings = [];

    public function getMacroDirectories(): array
    {
        return $this->macroDirectories;
    }

    public function setMacroDirectories(array $directories): self
    {
        $this->macroDirectories = $directories;
        return $this;
    }

    public function addMacroDirectory(string $directory): self
    {
        $this->macroDirectories[] = $directory;
        return $this;
    }

    public function getMacroFiles(): array
    {
        return $this->macroFiles;
    }

    public function setMacroFiles(array $files): self
    {
        $this->macroFiles = $files;
        return $this;
    }

    public function addMacroFile(string $file): self
    {
        $this->macroFiles[] = $file;
        return $this;
    }

    public function isPreserveLineNumbers(): bool
    {
        return $this->preserveLineNumbers;
    }

    public function setPreserveLineNumbers(bool $preserve): self
    {
        $this->preserveLineNumbers = $preserve;
        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function setPlugins(array $plugins): self
    {
        $this->plugins = $plugins;
        return $this;
    }

    public function addPlugin(string $pluginClass): self
    {
        $this->plugins[] = $pluginClass;
        return $this;
    }

    public function getCustomSetting(string $key, mixed $default = null): mixed
    {
        return $this->customSettings[$key] ?? $default;
    }

    public function setCustomSetting(string $key, mixed $value): self
    {
        $this->customSettings[$key] = $value;
        return $this;
    }

    public function loadFromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Configuration file not found: {$filePath}");
        }

        $config = require $filePath;
        
        if (!is_array($config)) {
            throw new \InvalidArgumentException("Configuration file must return an array");
        }

        if (isset($config['macro_directories'])) {
            $this->setMacroDirectories($config['macro_directories']);
        }

        if (isset($config['macro_files'])) {
            $this->setMacroFiles($config['macro_files']);
        }

        if (isset($config['preserve_line_numbers'])) {
            $this->setPreserveLineNumbers($config['preserve_line_numbers']);
        }

        if (isset($config['verbose'])) {
            $this->setVerbose($config['verbose']);
        }

        if (isset($config['plugins'])) {
            $this->setPlugins($config['plugins']);
        }

        if (isset($config['custom'])) {
            foreach ($config['custom'] as $key => $value) {
                $this->setCustomSetting($key, $value);
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            'macro_directories' => $this->macroDirectories,
            'macro_files' => $this->macroFiles,
            'preserve_line_numbers' => $this->preserveLineNumbers,
            'verbose' => $this->verbose,
            'plugins' => $this->plugins,
            'custom' => $this->customSettings,
        ];
    }
} 
