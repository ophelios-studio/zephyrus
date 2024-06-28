<?php namespace Zephyrus\Application\Views;

use JsPhpize\JsPhpizePhug;
use Phug\Optimizer;
use Phug\Phug;
use Phug\PhugException;
use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Utilities\FileSystem\Directory;

class PhugEngine implements RenderEngine
{
    public const NAME = "phug";
    public const DEFAULT_CONFIGURATIONS = [
        'cache_enabled' => true, // Enable the cache feature
        'cache_directory' => ROOT_DIR . "/cache/pug", // Cache directory for generated files
        'cache_update' => 'always', // always|never (useful in production if cache is done manually)
        'js_syntax' => true, // Enable JsPhpizePhug extension
        'debug_enabled' => true, // Enable Pug debugging
        'optimizer_enabled' => true // Enable Pug Optimizer
    ];

    /**
     * Loaded configurations for the Pug engine.
     *
     * @var array
     */
    private array $configurations;

    /**
     * Determine if the rendering should use the optimized call.
     *
     * @var bool
     */
    private bool $optimizerEnabled = false;

    /**
     * Keeps the internal Phug instance options.
     *
     * @var array
     */
    private array $options = [];

    /**
     * @throws PhugException
     */
    public function __construct(array $configurations = [])
    {
        $this->initializeConfigurations($configurations);
        $this->initializeCache();
        $this->initializeDebug();
        $this->initializeOptimizer();
        $this->initializeJsExtension();
        $this->initializeDefaultSharedVariables();
    }

    public function renderFromString(string $pugCode, array $args = []): string
    {
        return Phug::render($pugCode, $args, $this->options);
    }

    public function renderFromFile(string $page, array $args = []): void
    {
        $realPath = realpath(ROOT_DIR . '/app/Views/' . $page . '.pug');
        if (!file_exists($realPath) || !is_readable($realPath)) {
            throw new RuntimeException("The specified view file [$page] is not available (not readable or does not exists)");
        }
        if ($this->optimizerEnabled) {
            Optimizer::call('displayFile', [$realPath, $args], $this->options);
            return;
        }
        Phug::displayFile($realPath, $args, $this->options);
    }

    /**
     * Includes a variable or callback to Pug files rendered with this Pug Engine instance.
     *
     * @param string $name
     * @param mixed $action
     * @return void
     */
    public function share(string $name, mixed $action): void
    {
        $this->options['shared_variables'][$name] = $action;
    }

    /**
     * Add a filter which can then be used in every Pug files. E.g :add(value=4) 5. The callback must have 2 arguments:
     * the first one is the text and the second one the options given. In the above example, the text would be 5 and the
     * options would be an associative array with value=4.
     *
     * @param string $name
     * @param callable $callback
     * @throws PhugException
     * @return void
     */
    public function addFilter(string $name, callable $callback): void
    {
        Phug::setFilter($name, $callback);
    }

    private function initializeConfigurations(array $configurations): void
    {
        if (empty($configurations)) {
            $configurations = Configuration::read('pug') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
    }

    private function initializeCache(): void
    {
        $cacheEnabled = (isset($this->configurations['cache_enabled']))
            ? (bool) $this->configurations['cache_enabled']
            : self::DEFAULT_CONFIGURATIONS['cache_enabled'];
        $cacheDirectory = (isset($this->configurations['cache_directory']))
            ? $this->configurations['cache_directory']
            : self::DEFAULT_CONFIGURATIONS['cache_directory'];
        $cacheUpdate = (isset($this->configurations['cache_update']))
            ? $this->configurations['cache_update']
            : self::DEFAULT_CONFIGURATIONS['cache_update'];
        if ($cacheEnabled && !Directory::exists($cacheDirectory)) {
            Directory::create($cacheDirectory);
        }
        $this->options['cache_dir'] = $cacheEnabled ? $cacheDirectory : false;
        $this->options['up_to_date_check'] = ($cacheUpdate == "always");
    }

    private function initializeDebug(): void
    {
        $debugEnabled = (isset($this->configurations['debug_enabled']))
            ? (bool) $this->configurations['debug_enabled']
            : self::DEFAULT_CONFIGURATIONS['debug_enabled'];
        $this->options['debug'] = $debugEnabled;
    }

    private function initializeOptimizer(): void
    {
        $this->optimizerEnabled = (isset($this->configurations['optimizer_enabled']))
            ? (bool) $this->configurations['optimizer_enabled']
            : self::DEFAULT_CONFIGURATIONS['optimizer_enabled'];
    }

    /**
     * @throws PhugException
     */
    private function initializeJsExtension(): void
    {
        $jsEnabled = (isset($this->configurations['js_syntax']))
            ? (bool) $this->configurations['js_syntax']
            : self::DEFAULT_CONFIGURATIONS['js_syntax'];
        if ($jsEnabled) {
            Phug::addExtension(JsPhpizePhug::class);
        }
    }

    private function initializeDefaultSharedVariables(): void
    {
        $this->options['shared_variables'] = [];
    }
}
