<?php namespace Zephyrus\Exceptions\Asset;

use Throwable;
use Zephyrus\Exceptions\ZephyrusException;

abstract class AssetException extends ZephyrusException
{
    /**
     * Groups all exception related to the asset serving. All children exception classes have a code starting
     * from 16000. All messages will be automatically prefixed by "ZEPHYRUS ASSET: ...".
     */
    public function __construct(string $message = "", int $code = 16000, ?Throwable $previous = null)
    {
        parent::__construct('ASSET: ' . $message, $code, $previous);
    }
}
