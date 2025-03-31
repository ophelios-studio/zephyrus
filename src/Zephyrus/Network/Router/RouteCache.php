<?php namespace Zephyrus\Network\Router;

use Psr\SimpleCache\InvalidArgumentException;
use Zephyrus\Core\Cache\ApcuCache;

class RouteCache
{
    private const CACHE_ROUTE_KEY = 'router_repository';
    private const CACHE_UPDATE_KEY = 'router_repository_update_time';

    private ApcuCache $cache;

    public function __construct()
    {
        $this->cache = new ApcuCache();
    }

    public function read(): ?array
    {
        return $this->cache->get(self::CACHE_ROUTE_KEY);
    }

    public function isOutdated(int $time): bool
    {
        if (!$this->cache->has(self::CACHE_ROUTE_KEY)) {
            return true;
        }
        $lastUpdate = $this->cache->get(self::CACHE_UPDATE_KEY) ?? 0;
        return $lastUpdate < $time;
    }

    /**
     * Saves the instance currently defined routes into the APCu PHP cache. To optimize futur calls, the method
     * initializeFromCache() should be called.
     *
     * @param array $routes
     * @return void
     * @throws InvalidArgumentException
     */
    public function cache(array $routes): void
    {
        $this->cache->set(self::CACHE_ROUTE_KEY, $routes);
        $this->cache->set(self::CACHE_UPDATE_KEY, time());
    }

    public function clear(): void
    {
        $this->cache->delete(self::CACHE_ROUTE_KEY);
        $this->cache->delete(self::CACHE_UPDATE_KEY);
    }
}
