<?php namespace Zephyrus\Security;

use Zephyrus\Core\Configuration\Security\CsrfConfiguration;
use Zephyrus\Core\Session;
use Zephyrus\Exceptions\Security\InvalidCsrfException;
use Zephyrus\Exceptions\Security\MissingCsrfException;
use Zephyrus\Network\Request;

class CsrfGuard
{
    public const HEADER_TOKEN = 'HTTP_X_CSRF_TOKEN';
    public const REQUEST_TOKEN_VALUE = 'CSRFToken';
    public const TOKEN_LENGTH = 48;

    /**
     * Keeps a linked reference to the Request instance given in the constructor. Meaning that the request could evolve
     * outside the CsrfGuard instance and still be up-to-date.
     *
     * @var Request|null
     */
    private ?Request $request;
    private CsrfConfiguration $configuration;

    public static function generate(): string
    {
        $name = self::generateFormName();
        $token = self::generateToken($name);
        return $name . '$' . $token;
    }

    public function __construct(Request &$request, CsrfConfiguration $configuration)
    {
        $this->request = &$request;
        $this->configuration = $configuration;
    }

    /**
     * Verifies if the CSRF mitigation is enabled based on the instance configuration. Should be use as a condition to
     * execute the run method.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configuration->isEnabled();
    }

    /**
     * Verifies if the CSRF mitigation is configured to automatically inject HTML into forms.
     *
     * @return bool
     */
    public function isHtmlIntegrationEnabled(): bool
    {
        return $this->configuration->isHtmlIntegrationEnabled();
    }

    /**
     * Generates and returns the corresponding HTML hidden fields for the CSRF mitigation. Should be used for a custom
     * approach to form data injection. Not needed if the html_integration_enabled configuration. In that case, the
     * injectForms() method should be used instead.
     *
     * @return string
     */
    public function generateHiddenFields(): string
    {
        $value = self::generate();
        return '<input type="hidden" name="' . self::REQUEST_TOKEN_VALUE . '" value="' . $value . '" />';
    }

    /**
     * Proceeds to filter the current request for any CSRF mismatch. Forms must provide its unique name and
     * corresponding generated csrf token. Will throw a InvalidCsrfException on failure.
     *
     * @throws MissingCsrfException
     * @throws InvalidCsrfException
     */
    public function run(): void
    {
        if (!$this->isExempt()) {
            $providedToken = $this->getProvidedCsrfToken();
            if (is_null($providedToken)) {
                throw new MissingCsrfException($this->request);
            }
            $tokenParts = explode("$", $providedToken);
            if (count($tokenParts) < 2) {
                throw new InvalidCsrfException($this->request);
            }
            list($formName, $token) = $tokenParts;
            if (!$this->validateToken($formName, $token)) {
                throw new InvalidCsrfException($this->request);
            }
        }
    }

    /**
     * Automatically adds CSRF hidden fields to any forms present in the given HTML. This method is to be used with
     * automatic injection behavior. If a form contains a "nocsrf" HTML property, the CSRF mitigation is skipped for
     * this specific form.
     *
     * @param string $html
     * @return string
     */
    public function injectForms(string $html): string
    {
        preg_match_all("/<form(.*?)>(.*?)<\\/form>/is", $html, $matches, PREG_SET_ORDER);
        if (is_array($matches)) {
            foreach ($matches as $match) {
                if (str_contains($match[1], "nocsrf")) {
                    continue;
                }
                $hiddenFields = self::generateHiddenFields();
                $html = str_replace($match[0], "<form$match[1]>$hiddenFields$match[2]</form>", $html);
            }
        }
        return $html;
    }

    /**
     * @return bool
     */
    public function isGetSecured(): bool
    {
        return in_array('GET', $this->configuration->getGuardedMethods());
    }

    /**
     * @return bool
     */
    public function isPostSecured(): bool
    {
        return in_array('POST', $this->configuration->getGuardedMethods());
    }

    /**
     * @return bool
     */
    public function isPutSecured(): bool
    {
        return in_array('PUT', $this->configuration->getGuardedMethods());
    }

    /**
     * @return bool
     */
    public function isPatchSecured(): bool
    {
        return in_array('PATCH', $this->configuration->getGuardedMethods());
    }

    /**
     * @return bool
     */
    public function isDeleteSecured(): bool
    {
        return in_array('DELETE', $this->configuration->getGuardedMethods());
    }

    /**
     * Validates if the current request is exempt of CSRF verification. Can happen if the HTTP request method is not
     * filtered or the route matches one of the defined exceptions.
     *
     * @return bool
     */
    private function isExempt(): bool
    {
        if (!$this->isHttpMethodFiltered($this->request->getMethod()->value)) {
            return true;
        }
        foreach ($this->configuration->getExceptions() as $exceptionRegex) {
            if ($exceptionRegex === $this->request->getRoute()) {
                return true;
            }
            if (preg_match('/^' . $exceptionRegex . '$/', $this->request->getRoute())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generates and stores in the current session a cryptographically random token that shall be validated during the
     * run method.
     *
     * @param string $formName
     * @return string
     */
    private static function generateToken(string $formName): string
    {
        $token = Cryptography::randomString(self::TOKEN_LENGTH);
        $csrfData = Session::get('__CSRF_TOKEN', []);
        $csrfData[$formName] = $token;
        Session::set('__CSRF_TOKEN', $csrfData);
        return $token;
    }

    /**
     * Returns a random name to be used for a form csrf token.
     *
     * @return string
     */
    private static function generateFormName(): string
    {
        return "CSRFGuard_" . mt_rand(0, mt_getrandmax());
    }

    /**
     * Validates the given token with the one stored for the specified form name. Once validated, good or not, the token
     * is removed from the session.
     *
     * @param string $formName
     * @param string $token
     * @return bool
     */
    private function validateToken(string $formName, string $token): bool
    {
        $sortedCsrf = $this->getStoredCsrfToken($formName);
        if (!is_null($sortedCsrf)) {
            $csrfData = Session::get('__CSRF_TOKEN', []);
            if (is_null($this->request->getHeader('CSRF_KEEP_ALIVE'))
                && is_null($this->request->getParameter('CSRF_KEEP_ALIVE'))) {
                $csrfData[$formName] = '';
                Session::set('__CSRF_TOKEN', $csrfData);
            }
            return hash_equals($sortedCsrf, $token);
        }
        return false;
    }

    /**
     * Obtains the CSRF token stored by the server for the corresponding client. Returns null if undefined.
     *
     * @param string $formName
     * @return null|string
     */
    private function getStoredCsrfToken(string $formName): ?string
    {
        $csrfData = Session::get('__CSRF_TOKEN');
        if (is_null($csrfData)) {
            return null;
        }
        return $csrfData[$formName] ?? null;
    }

    /**
     * Obtains the CSRF token provided by the client either by request data or by an HTTP header (e.g. Ajax based
     * requests). Returns null if undefined.
     *
     * @return null|string
     */
    private function getProvidedCsrfToken(): ?string
    {
        $token = $this->request->getParameter(self::REQUEST_TOKEN_VALUE);
        if (is_null($token)) {
            $token = $this->request->getHeader(self::HEADER_TOKEN);
        }
        return $token;
    }

    /**
     * Checks if the specified method should be filtered.
     *
     * @param string $method
     * @return bool
     */
    private function isHttpMethodFiltered(string $method): bool
    {
        if ($this->isGetSecured() && $method == "GET") {
            return true;
        } elseif ($this->isPostSecured() && $method == "POST") {
            return true;
        } elseif ($this->isPutSecured() && $method == "PUT") {
            return true;
        } elseif ($this->isPatchSecured() && $method == "PATCH") {
            return true;
        } elseif ($this->isDeleteSecured() && $method == "DELETE") {
            return true;
        }
        return false;
    }
}
