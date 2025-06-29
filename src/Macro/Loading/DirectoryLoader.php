<?php

declare(strict_types=1);

namespace Syn\Macro\Loading;

class DirectoryLoader
{
    private FileLoader $fileLoader;

    public function __construct(FileLoader $fileLoader)
    {
        $this->fileLoader = $fileLoader;
    }

    public function loadFromDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Directory not found: {$directory}");
        }

        $files = glob($directory . '/*.syn');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                $this->fileLoader->loadFromFile($file);
            }
        }
    }
} 
