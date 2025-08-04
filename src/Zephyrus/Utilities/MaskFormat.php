<?php namespace Zephyrus\Utilities;

use BadMethodCallException;

final class MaskFormat
{
    private static array $customMasks = [];

    public static function email(?string $email): string
    {
        if (!str_contains($email, "@")) {
            return "-";
        }
        list($prefix, $suffix) = explode('@', $email);
        if (strlen($prefix) > 3) {
            $begin = $prefix[0] . $prefix[1] . '***' . $prefix[strlen($prefix) - 1];
        } else {
            $begin = '***';
        }
        return $begin . '@' . $suffix;
    }

    public static function phone(?string $phone): string
    {
        return empty($phone) ? '-' : '*******-' . substr($phone, -4);
    }

    public static function register(string $name, $callback): void
    {
        self::$customMasks[$name] = $callback;
    }

    public static function hasCustomFormatter(string $name): bool
    {
        return isset(self::$customMasks[$name]);
    }

    public static function __callStatic($method, $parameters): mixed
    {
        if (!self::hasCustomFormatter($method)) {
            throw new BadMethodCallException("Method $method does not exist.");
        }
        $customFormatter = self::$customMasks[$method];
        return call_user_func_array($customFormatter, $parameters);
    }
}
