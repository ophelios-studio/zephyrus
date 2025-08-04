<?php namespace Zephyrus\Core;

use RuntimeException;
use stdClass;
use Zephyrus\Application\Localization;
use Zephyrus\Application\Models\Language;
use Zephyrus\Application\Views\LatteEngine;
use Zephyrus\Application\Views\PhpEngine;
use Zephyrus\Application\Views\RenderEngine;
use Zephyrus\Exceptions\LocalizationException;
use Zephyrus\Exceptions\Session\SessionDatabaseStructureException;
use Zephyrus\Exceptions\Session\SessionDatabaseTableException;
use Zephyrus\Exceptions\Session\SessionDisabledException;
use Zephyrus\Exceptions\Session\SessionException;
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

class Application
{
    protected static ?Application $instance = null;

    protected Request $request;
    protected ?RenderEngine $renderEngine = null;
    protected ?Session $session = null;
    protected ?Localization $localization = null;
    protected array $supportedLanguages;

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            throw new RuntimeException("Application instance must first be initialized with constructor.");
        }
        return self::$instance;
    }

    /**
     * @throws SessionException
     * @throws LocalizationException
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->supportedLanguages = [];
        $this->initializeSession();
        $this->initializeLocalization();
        self::$instance = $this;
    }

    /**
     * Retrieves the loaded RenderEngine (used for rendering HTML). Proceeds with an initialization if the engine was
     * not yet initiated (default to Latte).
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

    /**
     * Can be overridden to apply any custom logics to get the application version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return Configuration::getApplication('version', 'dev:no-version');
    }

    /**
     * Allows you to set a custom RenderEngine class which is not yet supported by Zephyrus (e.g., Blade template
     * engine).
     *
     * @param RenderEngine $renderEngine
     */
    public function setRenderEngine(RenderEngine $renderEngine): void
    {
        $this->renderEngine = $renderEngine;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * This method retrieves the supported language object for the application (which contains the properties locale,
     * lang_code, country_code, flag_emoji, country and lang).
     *
     * @return Language[]
     */
    public function getSupportedLanguages(): array
    {
        if (empty($this->supportedLanguages)) {
            $languages = [];
            $supportedLocales = Configuration::getApplication('supported_locales', ['fr_CA']);
            $installedLanguages = $this->localization->getInstalledLanguages();
            foreach ($supportedLocales as $locale) {
                if (key_exists($locale, $installedLanguages)) {
                    $languages[] = Language::build($installedLanguages[$locale]);
                }
            }
            $this->supportedLanguages = $languages;
        }
        return $this->supportedLanguages;
    }

    public function getCurrentLanguage(): Language
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
    }

    /**
     * @throws LocalizationException
     */
    protected function initializeLocalization(): void
    {
        // If engine cannot properly start an exception will be thrown and must be corrected
        // to use this feature. Common errors are syntax error in json files. The exception
        // messages should be explicit enough.
        $this->localization = new Localization(Configuration::getLocale());
        $this->localization->start();
    }

    private function initializeRenderEngine(): void
    {
        $renderEngineName = Configuration::getRender('engine', LatteEngine::NAME);
        $this->renderEngine = match ($renderEngineName) {
            LatteEngine::NAME => new LatteEngine(Configuration::getRender()
                ?? LatteEngine::DEFAULT_CONFIGURATIONS),
            PhpEngine::NAME => new PhpEngine()
        };
    }
}
