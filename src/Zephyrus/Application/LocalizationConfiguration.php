<?php namespace Zephyrus\Application;

class LocalizationConfiguration
{
    public const DEFAULT_CONFIGURATIONS = [
        'path' => ROOT_DIR . '/locale', // Path to locales
        'cache' => ROOT_DIR . '/cache/locale', // Path to cache root
        'locale' => 'fr_CA', // Default language
        'charset' => 'utf8', // Default charset
        'timezone' => 'America/Montreal', // Default timezone
        'currency' => 'CAD' // Default currency
    ];

    private array $configurations;
    private string $path;
    private string $cache;
    private string $locale;
    private string $charset;
    private string $timezone;
    private string $currency;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializePath();
        $this->initializeCache();
        $this->initializeLocale();
        $this->initializeCharset();
        $this->initializeTimezone();
        $this->initializeCurrency();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCachePath(): string
    {
        return $this->cache;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeLocale(): void
    {
        $this->locale = $this->configurations['locale']
            ?? self::DEFAULT_CONFIGURATIONS['locale'];
    }

    private function initializePath(): void
    {
        $this->path = $this->configurations['path']
            ?? self::DEFAULT_CONFIGURATIONS['path'];
    }

    private function initializeCache(): void
    {
        $this->cache = $this->configurations['cache']
            ?? self::DEFAULT_CONFIGURATIONS['cache'];
    }

    private function initializeCharset(): void
    {
        $this->charset = $this->configurations['charset']
            ?? self::DEFAULT_CONFIGURATIONS['charset'];
    }

    private function initializeTimezone(): void
    {
        $this->timezone = $this->configurations['timezone']
            ?? self::DEFAULT_CONFIGURATIONS['timezone'];
    }

    private function initializeCurrency(): void
    {
        $this->currency = $this->configurations['currency']
            ?? self::DEFAULT_CONFIGURATIONS['currency'];
    }
}
