<?php

declare(strict_types=1);

namespace Syn\Plugin;

use Syn\Parser\MacroDefinition;

interface PluginInterface
{
    /**
     * Get the name of the plugin
     */
    public function getName(): string;

    /**
     * Get the version of the plugin
     */
    public function getVersion(): string;

    /**
     * Get the macros provided by this plugin
     * 
     * @return MacroDefinition[]
     */
    public function getMacros(): array;

    /**
     * Initialize the plugin
     * 
     * @param array $config Plugin configuration
     */
    public function initialize(array $config = []): void;

    /**
     * Check if the plugin is enabled
     */
    public function isEnabled(): bool;

    /**
     * Get plugin metadata
     */
    public function getMetadata(): array;
} 
