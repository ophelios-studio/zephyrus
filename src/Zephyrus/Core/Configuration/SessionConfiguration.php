<?php namespace Zephyrus\Core\Configuration;

use Zephyrus\Core\Session\SessionIdentifierManager;
use Zephyrus\Exceptions\Session\SessionLifetimeException;
use Zephyrus\Exceptions\Session\SessionRefreshRateException;
use Zephyrus\Exceptions\Session\SessionRefreshRateProbabilityException;
use Zephyrus\Exceptions\Session\SessionStorageModeException;
use Zephyrus\Exceptions\Session\SessionSupportedRefreshModeException;

class SessionConfiguration
{
    public const DEFAULT_DATABASE_TABLE = 'public.session';
    public const DEFAULT_LIFETIME = 1440; // 24 minutes

    public const DEFAULT_CONFIGURATIONS = [
        'enabled' => true, // Enable the whole session engine (should be false for API)
        'auto_start' => false, // Automatically start the session when the object is built. Else will need manual stating.
        'name' => '', // Name of the session cookie (empty means php.ini defaults)
        'lifetime' => self::DEFAULT_LIFETIME, // Seconds before the session is considered to be expired (defaults to 24 mins)
        'encrypted' => false,
        'storage' => 'file', // Type of internal storage handler to use (file | database)
        'save_path' => '', // Specific for storage type 'file'. Path where to save the session files (empty means php.ini defaults)
        'table' => self::DEFAULT_DATABASE_TABLE, // Specific for storage type 'database'. Schema and table name where the session should be stored (needs session_id, access and data columns)
        'fingerprint_ip' => false,
        'fingerprint_ua' => false,
        'refresh_mode' => 'none', // Algorithm to use to automatically refresh the sess id (none|probability|interval|request)
        'refresh_rate' => 0 // For probability (0-100), interval (nb of seconds), request (nb of requests)
    ];

    private array $configurations;
    private bool $enabled;
    private bool $autoStart;
    private string $name;
    private int $lifetime;
    private bool $encrypted;
    private string $storage;
    private string $savePath;
    private string $table;
    private bool $fingerprintIp;
    private bool $fingerprintUa;
    private string $refreshMode;
    private int $refreshRate;

    /**
     * @throws SessionStorageModeException
     * @throws SessionRefreshRateException
     * @throws SessionRefreshRateProbabilityException
     * @throws SessionLifetimeException
     * @throws SessionSupportedRefreshModeException
     */
    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeEnabled();
        $this->initializeName();
        $this->initializeLifetime();
        $this->initializeEncrypted();
        $this->initializeAutoStart();
        $this->initializeStorage();
        $this->initializeTable();
        $this->initializeFingerprints();
        $this->initializeRefreshMode();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isAutoStart(): bool
    {
        return $this->autoStart;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function getSavePath(): string
    {
        return $this->savePath;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function isIpFingerprintEnabled(): bool
    {
        return $this->fingerprintIp;
    }

    public function isUserAgentFingerprintEnabled(): bool
    {
        return $this->fingerprintUa;
    }

    public function getRefreshMode(): string
    {
        return $this->refreshMode;
    }

    public function getRefreshRate(): int
    {
        return $this->refreshRate;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeEnabled(): void
    {
        $this->enabled = (bool) ((isset($this->configurations['enabled']))
            ? $this->configurations['enabled']
            : self::DEFAULT_CONFIGURATIONS['enabled']);
    }

    private function initializeName(): void
    {
        $this->name = $this->configurations['name'] ?? self::DEFAULT_CONFIGURATIONS['name'];
        if (empty($this->name)) {
            $this->name = ini_get("session.name");
        }
    }

    /**
     * @throws SessionLifetimeException
     */
    private function initializeLifetime(): void
    {
        $lifetime = $this->configurations['lifetime'] ?? self::DEFAULT_CONFIGURATIONS['lifetime'];
        if (!is_numeric($lifetime)) {
            throw new SessionLifetimeException();
        }
        $this->lifetime = $lifetime;
    }

    private function initializeEncrypted(): void
    {
        $this->encrypted = (bool) ((isset($this->configurations['encrypted']))
            ? $this->configurations['encrypted']
            : self::DEFAULT_CONFIGURATIONS['encrypted']);
    }

    private function initializeAutoStart(): void
    {
        $this->autoStart = (bool) ((isset($this->configurations['auto_start']))
            ? $this->configurations['auto_start']
            : self::DEFAULT_CONFIGURATIONS['auto_start']);
    }

    /**
     * @throws SessionStorageModeException
     */
    private function initializeStorage(): void
    {
        $storage = $this->configurations['storage'] ?? self::DEFAULT_CONFIGURATIONS['storage'];
        if (!in_array($storage, ['file', 'database'])) {
            throw new SessionStorageModeException();
        }
        $this->storage = $storage;
    }

    private function initializeTable(): void
    {
        $this->table = $this->configurations['table'] ?? self::DEFAULT_CONFIGURATIONS['table'];
    }

    private function initializeSavePath(): void
    {
        $this->savePath = $this->configurations['save_path'] ?? self::DEFAULT_CONFIGURATIONS['save_path'];
    }

    private function initializeFingerprints(): void
    {
        $this->fingerprintUa = (bool) ((isset($this->configurations['fingerprint_ua']))
            ? $this->configurations['fingerprint_ua']
            : self::DEFAULT_CONFIGURATIONS['fingerprint_ua']);
        $this->fingerprintIp = (bool) ((isset($this->configurations['fingerprint_ip']))
            ? $this->configurations['fingerprint_ip']
            : self::DEFAULT_CONFIGURATIONS['fingerprint_ip']);
    }

    /**
     * @throws SessionRefreshRateException
     * @throws SessionRefreshRateProbabilityException
     * @throws SessionSupportedRefreshModeException
     */
    private function initializeRefreshMode(): void
    {
        $refreshMode = $this->configurations['refresh_mode']
            ?? self::DEFAULT_CONFIGURATIONS['refresh_mode'];
        $refreshRate = $this->configurations['refresh_rate']
            ?? self::DEFAULT_CONFIGURATIONS['refresh_rate'];
        if (!in_array($refreshMode, ['none', 'probability', 'interval', 'request'])) {
            throw new SessionSupportedRefreshModeException($refreshMode);
        }
        if (!is_int($refreshRate) || $refreshRate < 0) {
            throw new SessionRefreshRateException();
        }
        if ($refreshMode == SessionIdentifierManager::MODE_PROBABILITY && $refreshRate > 100) {
            throw new SessionRefreshRateProbabilityException();
        }
        $this->refreshMode = $refreshMode;
        $this->refreshRate = $refreshRate;
    }
}
