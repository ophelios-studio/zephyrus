<?php namespace Zephyrus\Security;

use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\Security\IntrusionDetectionException;
use Zephyrus\Network\Request;
use Zephyrus\Security\IntrusionDetection\IntrusionCache;
use Zephyrus\Security\IntrusionDetection\IntrusionMonitor;
use Zephyrus\Security\IntrusionDetection\IntrusionReport;
use Zephyrus\Security\IntrusionDetection\IntrusionRuleLoader;

class IntrusionDetection
{
    public const DEFAULT_CONFIGURATIONS = [
        'enabled' => false, // Enable the intrusion detection feature
        'custom_file' => '', // Change the default rule file
        'impact_threshold' => 0, // Minimum impact to be considered to throw an exception (default is any detection)
        'monitor_cookies' => true, // Verifies the content of request cookies
        'monitor_url' => true, // Verifies the content of the request URL
        'exceptions' => [] // List of request parameters to be exempt of detection (e.g. '__utmz')
    ];

    private array $configurations;
    private IntrusionMonitor $monitor;
    private array $exceptions = [];
    private int $impactThreshold = 0;
    private bool $enabled = true;
    private bool $includeCookiesMonitoring = true;
    private bool $includeUrlMonitoring = true;
    private ?IntrusionReport $report = null;
    private ?Request $request;

    public function __construct(?Request &$request, array $configurations = [])
    {
        $this->request = &$request;
        $this->initializeConfigurations($configurations);
        $this->initializeEnabledState();
        $this->initializeMonitor();
        $this->initializeExceptions();
        $this->initializeImpactThreshold();
        $this->initializeCookieMonitoring();
        $this->initializeUrlMonitoring();
    }

    /**
     * Execute the intrusion detection analysis using the specified monitored inputs. If an intrusion is detected, the
     * method will throw an exception.
     *
     * @throws IntrusionDetectionException
     */
    public function run(): void
    {
        $this->monitor->setExceptions($this->exceptions);
        $this->report = $this->monitor->run($this->getMonitoringInputs());
        if ($this->report->getImpact() > $this->impactThreshold) {
            throw new IntrusionDetectionException($this->report);
        }
    }

    /**
     * Verifies if the IDS monitoring is enabled based on the instance configuration. Should be use as a condition to
     * execute the run method.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Retrieves the last run produced report containing the overall details of the detections and execution time. Will
     * be null if the method run hasn't been called.
     *
     * @return IntrusionReport|null
     */
    public function getReport(): ?IntrusionReport
    {
        return $this->report;
    }

    private function initializeConfigurations(array $configurations): void
    {
        if (empty($configurations)) {
            $configurations = Configuration::getSecurity('ids') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
    }

    private function initializeMonitor(): void
    {
        $loader = new IntrusionRuleLoader($this->configurations['custom_file'] ?? null);
        $cache = new IntrusionCache();
        $intrusionRules = $cache->getRules();
        if (empty($intrusionRules)) {
            $intrusionRules = $loader->loadFromFile();
            $cache->cache($intrusionRules);
        }
        $this->monitor = new IntrusionMonitor($intrusionRules);
    }

    private function initializeExceptions(): void
    {
        if (isset($this->configurations['exceptions']) && !empty($this->configurations['exceptions'])) {
            $this->exceptions = $this->configurations['exceptions'];
        }
    }

    private function initializeCookieMonitoring(): void
    {
        if (isset($this->configurations['monitor_cookies']) && $this->configurations['monitor_cookies']) {
            $this->includeCookiesMonitoring = $this->configurations['monitor_cookies'];
        }
    }

    private function initializeUrlMonitoring(): void
    {
        if (isset($this->configurations['monitor_url']) && $this->configurations['monitor_url']) {
            $this->includeUrlMonitoring = $this->configurations['monitor_url'];
        }
    }

    private function initializeImpactThreshold(): void
    {
        if (isset($this->configurations['impact_threshold'])) {
            if (!is_int($this->configurations['impact_threshold'])) {
                throw new RuntimeException("IDS impact threshold configuration property must be int.");
            }
            $this->impactThreshold = $this->configurations['impact_threshold'];
        }
    }

    private function initializeEnabledState(): void
    {
        if (isset($this->configurations['enabled'])) {
            $this->enabled = (bool) $this->configurations['enabled'];
        }
    }

    /**
     * Prepares the request parameters to be verified by the IDS monitor. Will automatically include all request data
     * and cookies if included in configurations.
     *
     * @return array
     */
    private function getMonitoringInputs(): array
    {
        return [
            'parameters' => $this->request->getParameters(),
            'arguments' => $this->request->getArguments(),
            'cookies' => ($this->includeCookiesMonitoring) ? $this->request->getCookieJar()->getAll() : [],
            'url' => ($this->includeUrlMonitoring) ? ['requested_url' => $this->request->getRequestedUrl()] : [],
        ];
    }
}
