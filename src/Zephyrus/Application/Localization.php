<?php namespace Zephyrus\Application;

use Locale;
use stdClass;
use Zephyrus\Application\Models\Language;
use Zephyrus\Core\Configuration\LocalizationConfiguration;
use Zephyrus\Exceptions\LocalizationException;
use Zephyrus\Utilities\FileSystem\Directory;
use Zephyrus\Utilities\Formatter;

class Localization
{
    private LocalizationConfiguration $configuration;

    /**
     * Currently loaded application locale language. Maps to a directory within /locale.
     *
     * @var string|null
     */
    private ?string $appLocale = null;

    /**
     * Keeps a global reference for future uses of the complete language properties of the installed locale.
     *
     * @var array
     */
    private array $installedLanguages = [];

    /**
     * Holds the currently installed locales. Fetches the /locale directory and see what directories are available.
     *
     * @var array
     */
    private array $installedLocales = [];

    /**
     * Contains the complete cached localize texts as associative arrays. The keys are the locale (e.g. en_CA) and the
     * value is the whole associative array of localize keys.
     *
     * @var array
     */
    private array $cachedLocalizations = [];

    /**
     * Converts the 2 letters country code into the corresponding flag emoji.
     *
     * @param string $countryCode
     * @return string
     */
    public static function getFlagEmoji(string $countryCode): string
    {
        $codePoints = array_map(function ($char) {
            return 127397 + ord($char);
        }, str_split(strtoupper($countryCode)));
        return mb_convert_encoding('&#' . implode(';&#', $codePoints) . ';', 'UTF-8', 'HTML-ENTITIES');
    }

    public function __construct(LocalizationConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->buildInstalledLanguages();
        $this->buildInstalledLocales();
    }

    /**
     * Retrieves the list of all installed languages. Meaning all the directories under /locale. Will return an array
     * of stdClass containing all the details for each language : locale, lang_code, country_code, flag_emoji, country,
     * lang.
     *
     * @return Language[]
     */
    public function getInstalledLanguages(): array
    {
        return Language::buildArray($this->installedLanguages);
    }

    /**
     * Retrieves simply the names of the installed locales. For the complete object reference, use
     * getInstalledLanguages.
     *
     * @return string[]
     */
    public function getInstalledLocales(): array
    {
        return $this->installedLocales;
    }

    /**
     * Retrieves the actual loaded language. Will return an stdClass containing all the details : locale, lang_code,
     * country_code, flag_emoji, country, lang.
     *
     * @return Language
     */
    public function getLoadedLanguage(): Language
    {
        return Language::build($this->installedLanguages[$this->appLocale]);
    }

    /**
     * Initialize the localization environment based on the given configurations.
     *
     * @throws LocalizationException
     */
    public function start(): void
    {
        $this->initializeLocale();
        $this->generate();
    }

    /**
     * @param LocalizationConfiguration $configuration
     * @throws LocalizationException
     */
    public function changeLanguage(LocalizationConfiguration $configuration): void
    {
        $this->configuration = $configuration;
        $this->start();
    }

    public function getLocale(): string
    {
        return $this->appLocale;
    }

    public function getCurrency(): string
    {
        return $this->configuration->getCurrency();
    }

    public function getTimezone(): string
    {
        return $this->configuration->getTimezone();
    }

    public function getRegion(?string $locale = null): string
    {
        return locale_get_display_region($locale ?? $this->appLocale);
    }

    public function getLanguage(?string $locale = null): string
    {
        return locale_get_display_language($locale ?? $this->appLocale);
    }

    /**
     * Returns the entire caching array for the specified $locale (or the loaded one otherwise).
     *
     * @param string|null $locale
     * @return array
     */
    public function getCache(?string $locale = null): array
    {
        return $this->cachedLocalizations[$locale ?? $this->appLocale];
    }

    public function localize(string $key, array $args = []): string
    {
        // Extract locale to use
        $locale = $this->appLocale;
        $segments = explode(".", $key);
        $localizeIdentifier = $segments[0];
        if (in_array($localizeIdentifier, $this->getInstalledLocales())) {
            $locale = $localizeIdentifier;
            array_shift($segments);
        }

        // Find raw localize string
        $keys = $this->cachedLocalizations[$locale] ?? [];
        $result = null;
        foreach ($segments as $segment) {
            if (is_array($result)) {
                if (isset($result[$segment])) {
                    $result = $result[$segment];
                } else {
                    $result = null;
                    break;
                }
            } else {
                if (isset($keys[$segment])) {
                    $result = $keys[$segment];
                } else {
                    $result = null;
                    break;
                }
            }
        }
        $resultString = (is_null($result) || is_array($result)) ? $key : $result;

        // Extraction of sprintf arguments (%s) and named arguments ({var})
        $sprintfParameters = [];
        $namedParameters = [];

        foreach ($args as $index => $arg) {
            if (is_object($arg)) {
                $arg = get_object_vars($arg);
            }
            if (is_array($arg)) { // When used from localize function, it will be an array
                foreach ($arg as $innerIndex => $innerArg) {
                    if (is_string($innerIndex)) {
                        $namedParameters[$innerIndex] = $innerArg;
                    } else {
                        $sprintfParameters[] = $innerArg;
                    }
                }
            }
            if (is_string($index)) {
                $namedParameters[$index] = $arg;
            } else {
                $sprintfParameters[] = $arg;
            }
        }

        // Replace named arguments including format ({amount:money})
        if ($namedParameters) {
            $values = $this->extractCurlyBracesValues($resultString);
            $replaces = [];
            foreach ($values as $value) {
                $argumentName = $value;
                $argumentFormat = null;
                if (str_contains($value, ':')) {
                    list($argumentName, $argumentFormat) = explode(":", $value);
                }
                $replaces['{' . $value . '}'] = ($argumentFormat)
                    ? self::format($argumentFormat, $namedParameters[$argumentName])
                    : $namedParameters[$argumentName];
            }
            $resultString = strtr($resultString, $replaces);
        }

        // Replace sprintf arguments (%s)
        if ($sprintfParameters) {
            $parameters[] = $resultString;
            $resultString = call_user_func_array('sprintf', array_merge($parameters, $sprintfParameters));
        }

        return $resultString;
    }

    /**
     * Generates the localization cache for all installed languages if they are outdated or never created. Optionally,
     * you can force the whole regeneration with the boolean argument (will ignore the conditions and generate
     * everything from scratch). Throws an exception if json cannot be properly parsed.
     *
     * @param bool $force
     * @throws LocalizationException
     */
    public function generate(bool $force = false): void
    {
        foreach ($this->installedLocales as $locale) {
            if ($force || $this->prepareCache($locale) || $this->isCacheOutdated($locale)) {
                $this->generateCache($locale);
            }
        }
        $this->initializeCache();
    }

    /**
     * Removes the cache directory for the specified locale. If no locale is given, the whole cache directory will be
     * deleted.
     *
     * @param string|null $locale
     */
    public function clearCache(?string $locale = null): void
    {
        $cachePath = $this->configuration->getCachePath();
        $path = ($locale) ? $cachePath . "/$locale" : $cachePath;
        if (Directory::exists($path)) {
            (new Directory($path))->remove();
        }
    }

    /**
     * Generates a single language cache. Will completely remove any existing directories concerning this locale
     * beforehand and completely generate cache. Throws an exception if json cannot be properly parsed.
     *
     * @param string $locale
     * @throws LocalizationException
     */
    private function generateCache(string $locale): void
    {
        $cachePath = $this->configuration->getCachePath();
        $globalArray = $this->buildGlobalArrayFromJsonFiles($locale);
        $arrayCode = '<?php' . PHP_EOL . '$localizeCache = ' . var_export($globalArray, true) . ';' . PHP_EOL . 'return $localizeCache;' . PHP_EOL;
        file_put_contents($cachePath . "/$locale/generated.php", $arrayCode);
    }

    /**
     * Verifies if the cache needs to be regenerated for the specified locale.
     *
     * @param string $locale
     * @return bool
     */
    private function isCacheOutdated(string $locale): bool
    {
        $rootPath = $this->configuration->getPath();
        $cachePath = $this->configuration->getCachePath();
        $lastModifiedLocaleJson = $this->getDirectoryLastModifiedTime($rootPath . "/$locale");
        $lastModifiedLocaleCache = $this->getDirectoryLastModifiedTime($cachePath . "/$locale");
        return $lastModifiedLocaleJson > $lastModifiedLocaleCache;
    }

    /**
     * Creates the cache directory for the specified locale if they do not exist. Returns true if a directory was
     * created, false otherwise.
     *
     * @param string $locale
     * @return bool
     */
    private function prepareCache(string $locale): bool
    {
        $newlyCreated = false;
        $cachePath = $this->configuration->getCachePath();
        if (!Directory::exists($cachePath)) {
            Directory::create($cachePath);
            $newlyCreated = true;
        }

        $path = $cachePath . "/$locale";
        if (!Directory::exists($path)) {
            Directory::create($path);
            $newlyCreated = true;
        }
        return $newlyCreated;
    }

    /**
     * Builds an associative array containing all the json values to generate.
     *
     * @param string $locale
     * @throws LocalizationException
     * @return array
     */
    private function buildGlobalArrayFromJsonFiles(string $locale): array
    {
        $globalArray = [];
        foreach (Directory::recursiveGlob($this->configuration->getPath() . "/$locale/*.json") as $file) {
            $string = file_get_contents($file);
            $jsonAssociativeArray = json_decode($string, true);
            $jsonLastError = json_last_error();
            if ($jsonLastError > JSON_ERROR_NONE) {
                throw new LocalizationException($jsonLastError, $file);
            }

            // Merge values if key exists from another file. Allows to have the same localization key in multiple files
            // and merge them at generation time.
            foreach ($jsonAssociativeArray as $key => $values) {
                $globalArray[$key] = (key_exists($key, $globalArray))
                    ? array_replace_recursive($globalArray[$key], $values)
                    : $values;
            }
        }
        return $globalArray;
    }

    private function getDirectoryLastModifiedTime($directory)
    {
        $lastModifiedTime = 0;
        $directoryLastModifiedTime = filemtime($directory);
        foreach (glob("$directory/*") as $file) {
            $fileLastModifiedTime = (is_file($file)) ? filemtime($file) : $this->getDirectoryLastModifiedTime($file);
            $lastModifiedTime = max($fileLastModifiedTime, $directoryLastModifiedTime, $lastModifiedTime);
        }
        return $lastModifiedTime;
    }

    private function initializeLocale(): void
    {
        $this->appLocale = $this->configuration->getLocale();
        $charset = $this->configuration->getCharset();
        $locale = $this->appLocale . '.' . $charset;
        Locale::setDefault($this->appLocale);
        setlocale(LC_MESSAGES, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_CTYPE, $locale);
        putenv("LANG=" . $this->appLocale);
        date_default_timezone_set($this->configuration->getTimezone());
    }

    private function initializeCache(): void
    {
        $cachePath = $this->configuration->getCachePath();
        foreach ($this->installedLocales as $locale) {
            $this->cachedLocalizations[$locale] = require "$cachePath/$locale/generated.php";
        }
    }

    private function buildInstalledLocales(): void
    {
        $this->installedLocales = array_keys($this->installedLanguages);
    }

    private function buildInstalledLanguages(): void
    {
        $rootPath = $this->configuration->getPath();
        $dirs = array_filter(glob($rootPath . '/*'), 'is_dir');
        array_walk($dirs, function (&$value) {
            $value = basename($value);
        });
        $dirs = array_filter($dirs, function ($value) {
            return $value != "cache";
        });
        $languages = [];
        foreach ($dirs as $dir) {
            $languages[$dir] = $this->buildLanguage($dir);
        }
        $this->installedLanguages = $languages;
    }

    private function buildLanguage(string $locale): stdClass
    {
        $parts = explode("_", $locale);
        return (object) [
            'locale' => $locale,
            'lang_code' => $parts[0],
            'country_code' => $parts[1],
            'flag_emoji' => self::getFlagEmoji($parts[1]),
            'country' => $this->getRegion($locale),
            'lang' => $this->getLanguage($locale)
        ];
    }

    private function extractCurlyBracesValues(string $inputString) {
        $pattern = '/\{(.*?)\}/';
        $matches = [];
        preg_match_all($pattern, $inputString, $matches);
        return $matches[1];
    }

    private function format(string $type, mixed $value): string
    {
        return match ($type) {
            "money" => Formatter::money($value),
            "date" => Formatter::date($value),
            "time" => Formatter::time($value),
            "filesize" => Formatter::filesize($value),
            "datetime" => Formatter::dateTime($value),
            "decimal" => Formatter::decimal($value),
            "percent" => Formatter::percent($value),
            default => format($type, $value)
        };
    }
}
