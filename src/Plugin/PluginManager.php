<?php

declare(strict_types=1);

namespace Syn\Plugin;

use Syn\Parser\MacroDefinition;

class PluginManager
{
    private array $plugins = [];
    private array $config = [];

    public function registerPlugin(string $pluginClass): void
    {
        if (!class_exists($pluginClass)) {
            throw new \InvalidArgumentException("Plugin class not found: {$pluginClass}");
        }

        if (!is_subclass_of($pluginClass, PluginInterface::class)) {
            throw new \InvalidArgumentException("Plugin class must implement PluginInterface: {$pluginClass}");
        }

        $plugin = new $pluginClass();
        $this->plugins[$plugin->getName()] = $plugin;
    }

    public function registerPluginInstance(PluginInterface $plugin): void
    {
        $this->plugins[$plugin->getName()] = $plugin;
    }

    public function getPlugin(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }

    public function getAllPlugins(): array
    {
        return $this->plugins;
    }

    public function initializePlugins(array $config = []): void
    {
        $this->config = $config;

        foreach ($this->plugins as $plugin) {
            $pluginConfig = $config[$plugin->getName()] ?? [];
            $plugin->initialize($pluginConfig);
        }
    }

    public function getAllMacros(): array
    {
        $macros = [];

        foreach ($this->plugins as $plugin) {
            if ($plugin->isEnabled()) {
                $pluginMacros = $plugin->getMacros();
                foreach ($pluginMacros as $macro) {
                    $macros[] = $macro;
                }
            }
        }

        return $macros;
    }

    public function getMacrosByPlugin(string $pluginName): array
    {
        $plugin = $this->getPlugin($pluginName);
        if (!$plugin || !$plugin->isEnabled()) {
            return [];
        }

        return $plugin->getMacros();
    }

    public function enablePlugin(string $name): void
    {
        $plugin = $this->getPlugin($name);
        if ($plugin) {
            // This would require the plugin to have an enable method
            // For now, we'll just check if it's enabled
        }
    }

    public function disablePlugin(string $name): void
    {
        $plugin = $this->getPlugin($name);
        if ($plugin) {
            // This would require the plugin to have a disable method
            // For now, we'll just check if it's enabled
        }
    }

    public function getPluginMetadata(): array
    {
        $metadata = [];

        foreach ($this->plugins as $name => $plugin) {
            $metadata[$name] = [
                'name' => $plugin->getName(),
                'version' => $plugin->getVersion(),
                'enabled' => $plugin->isEnabled(),
                'macros_count' => count($plugin->getMacros()),
                'metadata' => $plugin->getMetadata(),
            ];
        }

        return $metadata;
    }

    public function clear(): void
    {
        $this->plugins = [];
        $this->config = [];
    }
} 
