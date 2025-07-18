<?php namespace Zephyrus\Utilities\Formatters;

use DateTime;
use IntlDateFormatter;
use Locale;
use Zephyrus\Application\Configuration;
use Zephyrus\Core\Application;

trait TimeFormatter
{
    public static function date(DateTime|string|null $dateTime): string
    {
        if (is_null($dateTime)) {
            return "-";
        }
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }

        $config = Configuration::getLanguage(Application::getInstance()->getCurrentLanguage()->locale);
        $format = $config['formats']['date'] ?? "d LLLL yyyy";

        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, $format);
        return $formatter->format($dateTime->getTimestamp()) ?: "-";
    }

    public static function datetime(DateTime|string|null $dateTime): string
    {
        if (is_null($dateTime)) {
            return "-";
        }
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $config = Configuration::getLanguage(Application::getInstance()->getCurrentLanguage()->locale);
        $format = $config['formats']['datetime'] ?? "d LLLL yyyy, HH:mm";
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::SHORT, null, null, $format);
        return $formatter->format($dateTime->getTimestamp()) ?: "-";
    }

    public static function time(DateTime|string|null $dateTime): string
    {
        if (is_null($dateTime)) {
            return "-";
        }
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $config = Configuration::getLanguage(Application::getInstance()->getCurrentLanguage()->locale);
        $format = $config['formats']['time'] ?? "HH:mm";
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::NONE, IntlDateFormatter::LONG, null, null, $format);
        return $formatter->format($dateTime->getTimestamp()) ?: "-";
    }

    public static function duration($seconds, $minuteSuffix = ":", $hourSuffix = ":", $secondSuffix = ""): string
    {
        return gmdate("H" . $hourSuffix . "i" . $minuteSuffix . "s" . $secondSuffix, $seconds);
    }
}
