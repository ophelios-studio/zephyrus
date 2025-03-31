<?php namespace Zephyrus\Core;

use Psr\SimpleCache\InvalidArgumentException;
use Zephyrus\Core\Cache\ApcuCache;
use Zephyrus\Exceptions\Asset\AssetUnavailableException;

class Asset
{
    private const CACHE_KEY = 'asset_hashes';
    private static ?array $cachedAssets = null;

    private string $publicPath;
    private string $path;
    private string $cacheIndex;

    /**
     * @param string $publicRelativePath path to the public resource as consumed by the browser (e.g. stylesheets/style.css).
     * @throws AssetUnavailableException
     * @throws InvalidArgumentException
     */
    public function __construct(string $publicRelativePath)
    {
        $publicRelativePath = ltrim($publicRelativePath, '/');
        $this->publicPath = "/$publicRelativePath";
        $this->path = ROOT_DIR . '/public/' . $publicRelativePath;
        $this->cacheIndex = "asset_" . md5($this->path);
        if (!file_exists($this->path)) {
            throw new AssetUnavailableException($this->publicPath);
        }
        if (is_null(self::$cachedAssets)) {
            $cache = new ApcuCache();
            self::$cachedAssets = $cache->get(self::CACHE_KEY, []);
        }
    }

    public function __toString(): string
    {
        return $this->publicPath . '?hash=' . $this->getHash();
    }

    public function getPublicPath(): string
    {
        return $this->publicPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHash(): string
    {
        $cache = new ApcuCache();
        $lastModified = $this->getLastModified();
        $assetData = self::$cachedAssets[$this->cacheIndex] ?? [];
        if ($assetData) {
            if ($assetData['last_modified'] < $lastModified) {
                $assetData = [
                    'hash' => $this->generateHash(),
                    'last_modified' => $lastModified,
                    'public_path' => $this->publicPath
                ];
                self::$cachedAssets[$this->cacheIndex] = $assetData;
                $cache->set(self::CACHE_KEY, self::$cachedAssets);
            }
        } else {
            $assetData = [
                'hash' => $this->generateHash(),
                'last_modified' => $lastModified,
                'public_path' => $this->publicPath
            ];
            self::$cachedAssets[$this->cacheIndex] = $assetData;
            $cache->set(self::CACHE_KEY, self::$cachedAssets);
        }
        return $assetData['hash'];
    }

    public function getLastModified(): int
    {
        return filemtime($this->path);
    }

    private function generateHash(): string
    {
        return md5_file($this->path);
    }
}
