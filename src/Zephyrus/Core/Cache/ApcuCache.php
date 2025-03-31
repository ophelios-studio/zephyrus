<?php namespace Zephyrus\Core\Cache;

use DateInterval;
use Zephyrus\Exceptions\ZephyrusRuntimeException;

class ApcuCache extends Cache
{
    /**
     * Verifies if the APCu extension is currently installed and enabled.
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    public function __construct()
    {
        if (!self::isAvailable()) {
            // @codeCoverageIgnoreStart
            throw new ZephyrusRuntimeException("APCu extension not installed or not enabled.");
            // @codeCoverageIgnoreEnd
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);
        if ($this->has($key)) {
            return apcu_fetch($key);
        }
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->assertValidKey($key);
        return apcu_store($key, $value, parent::convertTimeToLive($ttl));
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey($key);
        return apcu_delete($key);
    }

    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    public function has(string $key): bool
    {
        $this->assertValidKey($key);
        return apcu_exists($key);
    }

    /**
     * Retrieves all custom added elements to the APCu cache.
     *
     * @return array
     */
    public static function getList(): array
    {
        return apcu_cache_info()['cache_list'] ?? [];
    }
}
