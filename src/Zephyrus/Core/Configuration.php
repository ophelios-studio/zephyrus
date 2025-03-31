<?php namespace Zephyrus\Core;

use RuntimeException;
use Zephyrus\Core\Configuration\ConfigurationFile;
use Zephyrus\Core\Configuration\Security\SecurityConfiguration;
use Zephyrus\Core\Configuration\SessionConfiguration;
use Zephyrus\Exceptions\Session\SessionLifetimeException;
use Zephyrus\Exceptions\Session\SessionRefreshRateException;
use Zephyrus\Exceptions\Session\SessionRefreshRateProbabilityException;
use Zephyrus\Exceptions\Session\SessionStorageModeException;
use Zephyrus\Exceptions\Session\SessionSupportedRefreshModeException;

class Configuration
{
    public const CONFIGURATION_PATH = ROOT_DIR . '/config.yml';
    private static ?ConfigurationFile $configurationFile = null;

    /**
     * Retrieves the configurations of the given property.
     *
     * @param string|null $property
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public static function read(?string $property = null, mixed $defaultValue = null): mixed
    {
        if (is_null(self::$configurationFile)) {
            self::initializeConfigurations();
        }
        return self::$configurationFile->read($property, $defaultValue);
    }

    public static function getFile(): ?ConfigurationFile
    {
        return self::$configurationFile;
    }

    public static function write(?array $configurations): void
    {
        self::$configurationFile = null;
        if (!is_null($configurations)) {
            self::$configurationFile = new ConfigurationFile(self::CONFIGURATION_PATH);
            self::$configurationFile->write($configurations);
        }
    }

    public static function getApplication(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('application');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getRender(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('render');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getLocale(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('locale');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getMailer(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('mailer');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getDatabase(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('database');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    /**
     * @throws SessionStorageModeException
     * @throws SessionRefreshRateException
     * @throws SessionRefreshRateProbabilityException
     * @throws SessionLifetimeException
     * @throws SessionSupportedRefreshModeException
     */
    public static function getSession(): SessionConfiguration
    {
        return new SessionConfiguration(self::read('session')
            ?? SessionConfiguration::DEFAULT_CONFIGURATIONS);
    }

    public static function getSecurity(): SecurityConfiguration
    {
        return new SecurityConfiguration(self::read('security')
            ?? SecurityConfiguration::DEFAULT_CONFIGURATIONS);
    }

    /**
     * Parse the yml configuration file (/config.yml) into a PHP associative array including sections. Throws an
     * exception if file is not accessible.
     */
    private static function initializeConfigurations(): void
    {
        if (!is_readable(self::CONFIGURATION_PATH)) {
            throw new RuntimeException("Cannot parse configurations file [" . self::CONFIGURATION_PATH . "]");
        }
        self::$configurationFile = new ConfigurationFile(self::CONFIGURATION_PATH);
    }
}
