<?php namespace Zephyrus\Core;

use RuntimeException;
use stdClass;
use Zephyrus\Application\Bootstrap;
use Zephyrus\Application\Configuration;
use Zephyrus\Application\Localization;
use Zephyrus\Application\Views\PhugEngine;
use Zephyrus\Application\Views\RenderEngine;
use Zephyrus\Exceptions\LocalizationException;
use Zephyrus\Exceptions\Session\SessionDatabaseStructureException;
use Zephyrus\Exceptions\Session\SessionDatabaseTableException;
use Zephyrus\Exceptions\Session\SessionDisabledException;
use Zephyrus\Exceptions\Session\SessionFingerprintException;
use Zephyrus\Exceptions\Session\SessionHttpOnlyCookieException;
use Zephyrus\Exceptions\Session\SessionLifetimeException;
use Zephyrus\Exceptions\Session\SessionPathNotExistException;
use Zephyrus\Exceptions\Session\SessionPathNotWritableException;
use Zephyrus\Exceptions\Session\SessionRefreshRateException;
use Zephyrus\Exceptions\Session\SessionRefreshRateProbabilityException;
use Zephyrus\Exceptions\Session\SessionStorageModeException;
use Zephyrus\Exceptions\Session\SessionSupportedRefreshModeException;
use Zephyrus\Exceptions\Session\SessionUseOnlyCookiesException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Router;
use Zephyrus\Network\Router\RouteRepository;
use Zephyrus\Utilities\FileSystem\Directory;

class Application
{
    private static ?Application $instance = null;

    private Request $request;
    private ?RenderEngine $renderEngine = null;
    private ?Session $session = null;
    private ?Localization $localization = null;
    private array $supportedLanguages = [];

    public static function initiate(Request $request): Router
    {
        self::$instance = new self();
        self::$instance->request = $request;
        self::$instance->initializeNativeHelpers();
        self::$instance->initializeSession();
        self::$instance->initializeLocalization();
        return self::$instance->initializeRouter();
    }

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            throw new RuntimeException("Application instance must first be initialized with [Application::initiate()].");
        }
        return self::$instance;
    }

    /**
     * Retrieves the loaded PugEngine (used for rendering Pug files). Proceeds with an initialization if the engine was
     * not yet initiated (useful to avoid unnecessary class instanciations).
     *
     * @return RenderEngine
     */
    public function getRenderEngine(): RenderEngine
    {
        if (is_null($this->renderEngine)) {
            $this->initializeRenderEngine();
        }
        return $this->renderEngine;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * This method retrieves the supported language object for the application (which contains the properties locale,
     * lang_code, country_code, flag_emoji, country and lang).
     *
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        if (empty($this->supportedLanguages)) {
            $languages = [];
            $supportedLocales = Configuration::getApplication('supported_locales', ['fr_CA']);
            $installedLanguages = $this->localization->getInstalledLanguages();
            foreach ($supportedLocales as $locale) {
                if (key_exists($locale, $installedLanguages)) {
                    $languages[] = $installedLanguages[$locale];
                }
            }
            $this->supportedLanguages = $languages;
        }
        return $this->supportedLanguages;
    }

    public function getCurrentLanguage(): stdClass
    {
        return $this->localization->getLoadedLanguage();
    }

    public function getLocalization(): Localization
    {
        return $this->localization;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function getGitVersion(): string
    {
        $version = 'dev:nogit';
        if (file_exists(ROOT_DIR . "/.git")) {
            $version = shell_exec("git describe --tags");
            if (is_null($version)) { // Get revision commit if no tag exists
                $version = 'dev:' . shell_exec("git rev-parse --short HEAD");
            }
            if (str_contains($version, '-')) {
                $version = 'dev:' . $version;
            }
            $version = trim($version);
        }
        return $version;
    }

    /**
     * @throws SessionDatabaseStructureException
     * @throws SessionDatabaseTableException
     * @throws SessionStorageModeException
     * @throws SessionRefreshRateProbabilityException
     * @throws SessionLifetimeException
     * @throws SessionDisabledException
     * @throws SessionRefreshRateException
     * @throws SessionPathNotExistException
     * @throws SessionUseOnlyCookiesException
     * @throws SessionPathNotWritableException
     * @throws SessionSupportedRefreshModeException
     * @throws SessionFingerprintException
     * @throws SessionHttpOnlyCookieException
     */
    protected function initializeSession(): void
    {
        $this->session = new Session(Configuration::getSession());
        $this->session->setRequest($this->request);
        $this->session->start();
    }

    protected function initializeRenderEngine(): void
    {
        $this->initializePhugEngine();
    }

    protected function initializeLocalization(): void
    {
        try {
            $this->localization = new Localization(Configuration::getLocale());
            $this->localization->start();
        } catch (LocalizationException $e) {
            // If engine cannot properly start an exception will be thrown and must be corrected
            // to use this feature. Common errors are syntax error in json files. The exception
            // messages should be explicit enough.
            die($e->getMessage());
        }
    }

    protected function initializeNativeHelpers(): void
    {
        require_once(Bootstrap::getHelperFunctionsPath());
    }

    private function initializeRouter(): Router
    {
        $rootControllerPath = ROOT_DIR . '/app/Controllers';
        $routeRepository = new RouteRepository();
        if (!Directory::exists($rootControllerPath)) {
            return new Router($routeRepository);
        }

        $lastUpdate = (new Directory($rootControllerPath))->getLastModifiedTime();
        if ($routeRepository->isCacheOutdated($lastUpdate)) {
            Bootstrap::initializeControllerRoutes($routeRepository);
            $routeRepository->cache();
        } else {
            $routeRepository->initializeFromCache();
        }
        return new Router($routeRepository);
    }

    private function initializePhugEngine(): void
    {
        $this->renderEngine = new PhugEngine();
    }
}
