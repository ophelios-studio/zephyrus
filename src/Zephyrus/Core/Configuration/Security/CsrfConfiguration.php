<?php namespace Zephyrus\Core\Configuration\Security;

use RuntimeException;
use Zephyrus\Core\Configuration\Configuration;

class CsrfConfiguration extends Configuration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'enabled' => true, // Enable the CSRF mitigation feature
        'html_integration_enabled' => true, // Automatically insert needed HTML into forms
        'guard_methods' => ['POST', 'PUT', 'DELETE', 'PATCH'], // List of guarded methods
        'exceptions' => [] // List of route exceptions (e.g. ['\/test.*'] meaning all routes beginning with /test)
    ];

    /**
     * Determines the HTTP request methods that should be secured by the CSRF mitigation. It implies that for EVERY
     * request of these types, the CSRF token should be provided. All forms should follow a strict REST philosophy
     * meaning that all form processing should pass through POST, PUT, PATCH or DELETE only.
     *
     * @var array
     */
    private array $guardedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * List of routes to ignore the CSRF mitigation no matter the HTTP method. Normally, all routes for the guarded
     * HTTP methods should pass through the CSRF mitigation but, it may happen that some routes are exempt of
     * mitigation. For such cases, the exceptions should be used. Accepts regex for the route definition.
     *
     * @var array
     */
    private array $exceptions = [];

    /**
     * Determines if the CSRF mitigation is active. Should be verified before calling the run method.
     *
     * @var bool
     */
    private bool $enabled = true;

    /**
     * Determines if the CSRF mitigation should inject the needed HTML fields automatically. Since every form will need
     * proper inclusion of specific tokens, it is best to use the automatic integration.
     *
     * @var bool
     */
    private bool $htmlIntegrationEnabled = true;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        parent::__construct($configurations);
        $this->initializeEnabled();
        $this->initializeAutomaticHtmlIntegration();
        $this->initializeGuardedMethods();
        $this->initializeExceptions();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Verifies if the CSRF mitigation is configured to automatically inject HTML into forms.
     *
     * @return bool
     */
    public function isHtmlIntegrationEnabled(): bool
    {
        return $this->htmlIntegrationEnabled;
    }

    public function getGuardedMethods(): array
    {
        return $this->guardedMethods;
    }

    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    private function initializeEnabled(): void
    {
        $this->enabled = (bool) ((isset($this->configurations['enabled']))
            ? $this->configurations['enabled']
            : self::DEFAULT_CONFIGURATIONS['enabled']);
    }

    private function initializeAutomaticHtmlIntegration(): void
    {
        $this->htmlIntegrationEnabled = (bool) ((isset($this->configurations['html_integration_enabled']))
            ? $this->configurations['html_integration_enabled']
            : self::DEFAULT_CONFIGURATIONS['html_integration_enabled']);
    }

    private function initializeGuardedMethods(): void
    {
        $this->guardedMethods = $this->configurations['guard_methods']
            ?? self::DEFAULT_CONFIGURATIONS['guard_methods'];
        foreach ($this->guardedMethods as $method) {
            if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                // TODO: Throw Zephyrus Exception
                throw new RuntimeException("CSRF guard methods is invalid. Must be an array containing a combinaison of the following values 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'. ");
            }
        }
    }

    private function initializeExceptions(): void
    {
        $this->exceptions = $this->configurations['exceptions']
            ?? self::DEFAULT_CONFIGURATIONS['exceptions'];
    }
}
