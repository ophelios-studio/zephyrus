<?php namespace Zephyrus\Core\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Zephyrus\Exceptions\Cache\InvalidArgumentException;

abstract class Cache implements CacheInterface
{
    private const INVALID_KEY_CHARACTERS = '{}()/\@:';

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }
        return $results;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->delete($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Throws an exception if $key is invalid.
     *
     * @throws InvalidArgumentException
     */
    protected function assertValidKey(string $key): void
    {
        $this->assertNotEmptyKey($key);
        $this->assertNoInvalidCharacters($key);
    }

    /**
     * @param int|DateInterval|null $ttl
     * @return int
     *
     * From the Browscap project
     * @author Matthias Mullie <scrapbook@mullie.eu>
     * @copyright Copyright (c) 2014, Matthias Mullie. All rights reserved
     * @license LICENSE MIT
     */
    protected function convertTimeToLive(null|int|DateInterval $ttl): int
    {
        if (is_null($ttl)) {
            return 0;
        }

        if ($ttl instanceof DateInterval) {
            // convert DateInterval to integer by adding it to a 0 DateTime
            $datetime = new \DateTime();
            $datetime->setTimestamp(0);
            $datetime->add($ttl);
            $ttl = (int) $datetime->format('U');
        }

        /*
         * PSR-16 specifies that if `0` is provided, it must be treated as
         * expired, whereas KeyValueStore will interpret 0 to mean "never
         * expire".
         */
        if ($ttl === 0) {
            return -1;
        }

        /*
         * PSR-16 only accepts relative timestamps, whereas KeyValueStore
         * accepts both relative & absolute, depending on what the timestamp
         * is. We'll convert all timestamps > 30 days into absolute
         * timestamps; the others can remain relative, as KeyValueStore will
         * already treat those values as such.
         * @see https://github.com/dragoonis/psr-simplecache/issues/3
         */
        if ($ttl > 30 * 24 * 60 * 60) {
            return $ttl + time();
        }

        return $ttl;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertNoInvalidCharacters(string $key): void
    {
        if (preg_match('/[' . preg_quote(self::INVALID_KEY_CHARACTERS, "/") . ']/', $key)) {
            throw new InvalidArgumentException('PSR-16 prevents usage of the following key: [' . $key . '] because it contains one of the forbidden characters [' . self::INVALID_KEY_CHARACTERS . '].');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertNotEmptyKey(string $key): void
    {
        if (is_blank($key)) {
            throw new InvalidArgumentException('The cache key should not be empty.');
        }
    }
}
