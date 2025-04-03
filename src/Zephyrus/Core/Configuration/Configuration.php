<?php namespace Zephyrus\Core\Configuration;

abstract class Configuration
{
    protected array $configurations;

    /**
     * @param array $configurations
     */
    public function __construct(array $configurations)
    {
        $this->configurations = $configurations;
    }

    public function getConfigurations(): array
    {
        return $this->configurations;
    }

    public function getConfiguration(string $property, mixed $default = null): mixed
    {
        return $this->configurations[$property] ?? $default;
    }
}
