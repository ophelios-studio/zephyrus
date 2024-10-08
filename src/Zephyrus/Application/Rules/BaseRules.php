<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;

trait BaseRules
{
    /**
     * Field is required for submission, meaning its value is not empty (empty string ['', ""], null and false). Zero
     * values are considered okay in this context, e.g. 0, '0', 0.0, '0.0' would pass the required validation.
     *
     * @param string $errorMessage
     * @return Rule
     */
    public static function required(string $errorMessage = ""): Rule
    {
        return new Rule(function (mixed $data) {
            if (is_numeric($data)) {
                return true;
            }
            return !empty(is_string($data) ? trim($data) : $data);
        }, $errorMessage, 'required');
    }

    public static function decimal(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned)
            ? ['Zephyrus\Utilities\Validation', 'isDecimal']
            : ['Zephyrus\Utilities\Validation', 'isSignedDecimal'], $errorMessage, 'decimal');
    }

    public static function integer(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned)
            ? ['Zephyrus\Utilities\Validation', 'isInteger']
            : ['Zephyrus\Utilities\Validation', 'isSignedInteger'], $errorMessage, 'integer');
    }

    public static function range(int $min, int $max, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($min, $max) {
            return Validation::isInRange($data, $min, $max);
        }, $errorMessage, 'range');
    }

    public static function inArray(array $array, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($array) {
            return in_array($data, $array);
        }, $errorMessage, 'inArray');
    }

    public static function notInArray(array $array, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($array) {
            return !in_array($data, $array);
        }, $errorMessage, 'notInArray');
    }

    public static function sameAs(string $comparedFieldName, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data, $values) use ($comparedFieldName) {
            return isset($values[$comparedFieldName]) && $data == $values[$comparedFieldName];
        }, $errorMessage, 'sameAs');
    }

    public static function associativeArray(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return Validation::isAssociativeArray($data);
        }, $errorMessage, 'associativeArray');
    }

    public static function length(int $length, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($length) {
            return (is_array($data) && count($data) == $length)
                || (is_string($data) && strlen($data) == $length);
        }, $errorMessage, 'length');
    }

    public static function sameLengthAs(string $comparedFieldName, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data, $values) use ($comparedFieldName) {
            return isset($values[$comparedFieldName])
                && ((is_array($data) && is_array($values[$comparedFieldName]) && count($data) == count($values[$comparedFieldName]))
                    || (is_string($data) && is_string($values[$comparedFieldName]) && strlen($data) == strlen($values[$comparedFieldName])));
        }, $errorMessage, 'sameLengthAs');
    }

    public static function array(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_array($data);
        }, $errorMessage, 'array');
    }

    public static function arraySize(int $expectedSize, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($expectedSize) {
            return count($data) == $expectedSize;
        }, $errorMessage, 'arraySize');
    }

    public static function object(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_object($data);
        }, $errorMessage, 'object');
    }

    public static function arrayNotEmpty(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_array($data) && !empty($data);
        }, $errorMessage, 'arrayNotEmpty');
    }

    public static function boolean(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isBoolean'], $errorMessage, 'boolean');
    }

    public static function regex(string $pattern, string $errorMessage = "", string $modifiers = ""): Rule
    {
        return new Rule(function ($data) use ($pattern, $modifiers) {
            return Validation::isRegex($data, $pattern, $modifiers);
        }, $errorMessage, 'regex');
    }

    public static function regexInsensitive(string $pattern, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($pattern) {
            return Validation::isRegex($data, $pattern, "i");
        }, $errorMessage, 'regexInsensitive');
    }

    public static function onlyWithin(array $possibleValues, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($possibleValues) {
            return Validation::isOnlyWithin($data, $possibleValues);
        }, $errorMessage, "onlyWithin");
    }

    public static function lowerThan(float|int $threshold, string $errorMessage): Rule
    {
        return new Rule(function ($value) use ($threshold) {
            return $value < $threshold;
        }, $errorMessage, "lowerThan");
    }

    public static function lowerEqualsThan(float|int $threshold, string $errorMessage): Rule
    {
        return new Rule(function ($value) use ($threshold) {
            return $value <= $threshold;
        }, $errorMessage, "lowerEqualsThan");
    }

    public static function greaterThan(float|int $threshold, string $errorMessage): Rule
    {
        return new Rule(function ($value) use ($threshold) {
            return $value > $threshold;
        }, $errorMessage, "greaterThan");
    }

    public static function greaterEqualsThan(float|int $threshold, string $errorMessage): Rule
    {
        return new Rule(function ($value) use ($threshold) {
            return $value >= $threshold;
        }, $errorMessage, "greaterEqualsThan");
    }
}
