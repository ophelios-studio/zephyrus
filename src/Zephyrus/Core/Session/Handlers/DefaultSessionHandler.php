<?php namespace Zephyrus\Core\Session\Handlers;

use SessionHandler;
use Zephyrus\Exceptions\Session\SessionPathNotExistException;
use Zephyrus\Exceptions\Session\SessionPathNotWritableException;
use Zephyrus\Utilities\FileSystem\Directory;

class DefaultSessionHandler extends SessionHandler
{
    public function open(string $path, string $name): bool
    {
        return parent::open($path, $name);
    }

    public function close(): bool
    {
        return parent::close();
    }

    /**
     * @throws SessionPathNotWritableException
     * @throws SessionPathNotExistException
     */
    public function isAvailable(string $path): bool
    {
        if (!Directory::exists($path)) {
            throw new SessionPathNotExistException($path);
        }
        // @codeCoverageIgnoreStart
        if (!Directory::isWritable($path)) {
            throw new SessionPathNotWritableException($path);
        }
        // @codeCoverageIgnoreEnd
        return true;
    }
}
