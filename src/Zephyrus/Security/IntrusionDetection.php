<?php namespace Zephyrus\Security;

use Zephyrus\Core\Configuration\Security\IdsConfiguration;
use Zephyrus\Exceptions\Security\IntrusionDetectionException;
use Zephyrus\Network\Request;
use Zephyrus\Security\IntrusionDetection\IntrusionCache;
use Zephyrus\Security\IntrusionDetection\IntrusionMonitor;
use Zephyrus\Security\IntrusionDetection\IntrusionReport;
use Zephyrus\Security\IntrusionDetection\IntrusionRuleLoader;

class IntrusionDetection
{
    private Request $request;
    private IdsConfiguration $configuration;
    private IntrusionMonitor $monitor;
    private ?IntrusionReport $report = null;

    public function __construct(Request &$request, IdsConfiguration $configuration)
    {
        $this->request = &$request;
        $this->configuration = $configuration;
        $this->initializeMonitor();
    }

    /**
     * Execute the intrusion detection analysis using the specified monitored inputs. If an intrusion is detected, the
     * method will throw an exception.
     *
     * @throws IntrusionDetectionException
     */
    public function run(): void
    {
        $this->monitor->setExceptions($this->configuration->getExceptions());
        $this->report = $this->monitor->run($this->getMonitoringInputs());
        if ($this->report->getImpact() > $this->configuration->getImpactThreshold()) {
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
        return $this->configuration->isEnabled();
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

    private function initializeMonitor(): void
    {
        $loader = new IntrusionRuleLoader($this->configuration->getCustomFile());
        $cache = new IntrusionCache();
        $intrusionRules = $cache->getRules();
        if (empty($intrusionRules)) {
            $intrusionRules = $loader->loadFromFile();
            $cache->cache($intrusionRules);
        }
        $this->monitor = new IntrusionMonitor($intrusionRules);
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
            'cookies' => ($this->configuration->isCookieMonitoring()) ? $this->request->getCookieJar()->getAll() : [],
            'url' => ($this->configuration->isUrlMonitoring()) ? ['requested_url' => $this->request->getRequestedUrl()] : [],
        ];
    }
}
