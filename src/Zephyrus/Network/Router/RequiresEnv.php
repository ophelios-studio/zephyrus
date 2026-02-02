<?php namespace Zephyrus\Network\Router;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RequiresEnv
{
    private string $envName;
    private ?string $value;

    /**
     * The annotation will be used when resolving a route.
     *
     * @param string $envName
     * @param string|null $value
     */
    public function __construct(string $envName, ?string $value = null)
    {
        $this->envName = $envName;
        $this->value = $value;
    }

    public function isSatisfied(): bool
    {
        $envValue = $this->getValue();
        if (is_null($envValue)) {
            return false;
        }
        if (!is_null($this->value)) {
            return $envValue === $this->value;
        }
        return true;
    }

    private function getValue(): ?string
    {
        $candidates = [];
        $g = getenv($this->envName);
        if ($g !== false) {
            $candidates[] = $g;
        }
        if (isset($_ENV[$this->envName])) {
            $candidates[] = $_ENV[$this->envName];
        }
        if (isset($_SERVER[$this->envName])) {
            $candidates[] = $_SERVER[$this->envName];
        }
        foreach ($candidates as $val) {
            if (is_string($val)) {
                $val = trim($val);
                if ($val !== '') {
                    return $val;
                }
            }
        }
        return null;
    }
}
