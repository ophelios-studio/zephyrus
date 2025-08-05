<?php namespace Zephyrus\Core\Configuration;

use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

class ConfigurationFile
{
    private string $path;
    private array $content = [];

    public function __construct(string $filePath)
    {
        $this->path = $filePath;
        if (is_readable($this->path)) {
            try {
                $yamlData = Yaml::parseFile($this->path, Yaml::PARSE_CUSTOM_TAGS);
                $this->content = $this->processYamlTags($yamlData);
            } catch (ParseException $exception) {
                throw new RuntimeException("Unable to parse the YAML string [{$exception->getMessage()}]");
            }
        }
    }

    public function save(): void
    {
        if (!is_writable(dirname($this->path))) {
            throw new RuntimeException("Cannot write file [$this->path]");
        }
        $yaml = Yaml::dump($this->content);
        file_put_contents($this->path, $yaml);
    }

    public function read(?string $property = null, $defaultValue = null): mixed
    {
        return (is_null($property))
            ? $this->content
            : $this->content[$property] ?? $defaultValue;
    }

    public function write(array $content): void
    {
        $this->content = $content;
    }

    public function writeProperty(string $property, mixed $value): void
    {
        $this->content[$property] = $value;
    }

    private function processYamlTags(array $config): array
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->processYamlTags($value); // Process sub-arrays
            } elseif ($value instanceof TaggedValue) {
                if ($value->getTag() === 'env') {
                    $arguments = explode(',', $value->getValue());
                    $envKey = trim($arguments[0], "\"'"); // Get the ENV key
                    $default = isset($arguments[1]) ? trim($arguments[1], "\"'") : null;
                    $config[$key] = env($envKey, $default);
                }
            }
        }
        return $config;
    }
}
