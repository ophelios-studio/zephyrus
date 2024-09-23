<?php namespace Zephyrus\Utilities\Formatters;

use NumberFormatter;
use Zephyrus\Core\Application;

trait NumericFormatter
{
    public static function percent(int|float|null $number, int $minDecimals = 0, int $maxDecimals = 2): string
    {
        if (is_null($number)) {
            return "-";
        }
        $locale = Application::getInstance()->getLocalization()->getLocale();
        $formatter = new NumberFormatter($locale, NumberFormatter::PERCENT);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format($number, NumberFormatter::TYPE_DOUBLE);
        return $result === false ? "-" : $result;
    }

    public static function money(int|float|null $amount, bool $accounting = false, int $minDecimals = 2, int $maxDecimals = 2): string
    {
        if (is_null($amount)) {
            return "-";
        }
        $locale = Application::getInstance()->getLocalization()->getLocale();
        $formatter = new NumberFormatter($locale, $accounting ? NumberFormatter::CURRENCY_ACCOUNTING : NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        $result = $formatter->formatCurrency(round($amount, $maxDecimals), Application::getInstance()->getLocalization()->getCurrency());
        return $result === false ? "-" : $result;
    }

    public static function decimal(int|float|null $number, int $minDecimals = 2, int $maxDecimals = 2): string
    {
        if (is_null($number)) {
            return "-";
        }
        $locale = Application::getInstance()->getLocalization()->getLocale();
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format(round($number, $maxDecimals), NumberFormatter::TYPE_DOUBLE);
        return $result === false ? "-" : $result;
    }
}
