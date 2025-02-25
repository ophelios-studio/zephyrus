<?php namespace Zephyrus\Core\Entity;

use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;
use stdClass;
use ValueError;

abstract class Entity implements JsonSerializable
{
    private stdClass $rawData;

    /**
     * Creates an instance based on the database row which normally should be the result of the associated view.
     *
     * @param ?stdClass $row
     * @return ?static
     */
    public static function build(?stdClass $row): ?static
    {
        if (is_null($row)) {
            return null;
        }
        $instance = new static();
        $instance->rawData = $row;
        $reflection = new ReflectionClass($instance);
        foreach ($row as $name => $value) {
            if (property_exists($instance, $name)) {
                $reflectionType = $reflection->getProperty($name)->getType();
                if ($reflectionType instanceof ReflectionUnionType) { // Do not consider Obj1|Obj2 types
                    continue;
                }
                if (!$reflectionType->isBuiltin()) {
                    $className = $reflectionType->getName();
                    $innerReflection = new ReflectionClass($className);
                    if ($className == "stdClass" || $innerReflection->isSubclassOf(\StdClass::class)) {
                        $instance->$name = $value;
                    } elseif ($innerReflection->isEnum()) {
                        try {
                            $instance->$name = $className::from($value);
                        } catch (ValueError $e) {
                            throw new InvalidArgumentException("Invalid value for enum $className: " . $value);
                        }
                    } else if ($innerReflection->isSubclassOf(self::class)) {
                        $instance->$name = $className::build($value);
                    }
                } else {
                    if (!is_null($value)) { // Keep null as null
                        settype($value, $reflectionType->getName());
                    }
                    $instance->$name = $value;
                }
            }
        }
        return $instance;
    }

    public static function buildArray(array $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            $results[] = static::build($row);
        }
        return $results;
    }

    /**
     * Retrieves the raw data associated with the object which was used to build it in the first place.
     *
     * @return stdClass|null The raw data as a stdClass object, or null if no data is available.
     */
    public function getRawData(): ?stdClass
    {
        return $this->rawData;
    }

    /**
     * Prepares the object for JSON serialization by reflecting on its public properties. Excludes properties marked
     * with the JsonIgnore attribute.
     *
     * @return array An associative array representing the serialized data of the object.
     */
    public function jsonSerialize(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $data = [];
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(JsonIgnore::class);
            if (!empty($attributes)) {
                continue;
            }
            $data[$property->getName()] = $property->getValue($this);
        }
        return $data;
    }
}
