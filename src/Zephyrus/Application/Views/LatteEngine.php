<?php namespace Zephyrus\Application\Views;

use Latte\Engine;
use Latte\Extension;
use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Utilities\FileSystem\Directory;

class LatteEngine implements RenderEngine
{
    public const NAME = 'latte';
    public const DEFAULT_CONFIGURATIONS = [
        'cache_directory' => ROOT_DIR . "/cache/latte", // Cache directory for generated files
        'cache_update' => 'always' // always|never (useful in production if cache is done manually)
    ];

    /**
     * Loaded configurations for the Latte engine.
     *
     * @var array
     */
    private array $configurations;
    private Engine $engine;
    private string $cacheDirectory;
    private bool $cacheUpdate;

    public function __construct(array $configurations = [])
    {
        $this->initializeConfigurations($configurations);
        $this->initializeCache();
        $this->engine = new Engine;
        $this->engine->setTempDirectory($this->cacheDirectory);
        if (!$this->cacheUpdate) {
            $this->engine->setAutoRefresh(false);
        }
    }

    public function renderFromFile(string $page, array $args = []): void
    {
        $realPath = realpath(ROOT_DIR . '/app/Views/' . $page . '.latte');
        if (!file_exists($realPath) || !is_readable($realPath)) {
            throw new RuntimeException("The specified view file [$page] is not available (not readable or does not exists)");
        }
        $this->engine->render($realPath, $args);
    }

    public function addExtension(Extension $extension): void
    {
        $this->engine->addExtension($extension);
    }

    private function initializeConfigurations(array $configurations): void
    {
        if (empty($configurations)) {
            $configurations = Configuration::read('latte') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
    }

    private function initializeCache(): void
    {
        $cacheDirectory = (isset($this->configurations['cache_directory']))
            ? $this->configurations['cache_directory']
            : self::DEFAULT_CONFIGURATIONS['cache_directory'];
        $cacheUpdate = (isset($this->configurations['cache_update']))
            ? $this->configurations['cache_update']
            : self::DEFAULT_CONFIGURATIONS['cache_update'];
        if (!Directory::exists($cacheDirectory)) {
            Directory::create($cacheDirectory);
        }
        $this->cacheDirectory = $cacheDirectory;
        $this->cacheUpdate = $cacheUpdate == "always";
    }
}
