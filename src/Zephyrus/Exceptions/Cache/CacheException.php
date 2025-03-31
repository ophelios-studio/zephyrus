<?php namespace Zephyrus\Exceptions\Cache;

use Throwable;
use Zephyrus\Exceptions\ZephyrusException;

abstract class CacheException extends ZephyrusException
{
    /**
     * Groups all exception related to the caching processes. All children exception classes have a code starting
     * from 17000. All messages will be automatically prefixed by "ZEPHYRUS CACHE: ...".
     */
    public function __construct(string $message = "", int $code = 17000, ?Throwable $previous = null)
    {
        parent::__construct('CACHE: ' . $message, $code, $previous);
    }
}
