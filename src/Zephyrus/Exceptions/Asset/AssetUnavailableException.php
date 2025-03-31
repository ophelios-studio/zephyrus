<?php namespace Zephyrus\Exceptions\Asset;

class AssetUnavailableException extends AssetException
{
    private string $publicPath;

    public function __construct(string $publicPath)
    {
        $this->publicPath = $publicPath;
        parent::__construct("The requested public resource $publicPath has not been found.", 16001);
    }

    public function getPublicPath(): string
    {
        return $this->publicPath;
    }
}
