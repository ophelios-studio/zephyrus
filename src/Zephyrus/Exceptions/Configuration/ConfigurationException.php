<?php namespace Zephyrus\Exceptions\Configuration;

use Throwable;
use Zephyrus\Exceptions\ZephyrusException;

abstract class ConfigurationException extends ZephyrusException
{
    /**
     * Groups all exception related to the caching processes. All children exception classes have a code starting
     * from 18000. All messages will be automatically prefixed by "ZEPHYRUS CONFIGURATION: ...".
     */
    public function __construct(string $message = "", int $code = 18000, ?Throwable $previous = null)
    {
        parent::__construct('CONFIGURATION: ' . $message, $code, $previous);
    }
}
