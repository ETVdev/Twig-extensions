<?php
class Twig_Extensions_Extension_Intl extends Twig_Extension
{
    public function __construct()
    {
        if (!extension_loaded('intl')) {
            throw new RuntimeException('The intl extension is needed to use intl-based filters.');
        }
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('intl_date', 'twig_intl_date_filter', array('needs_environment' => true)),
            new Twig_SimpleFilter('intl_currency', 'twig_intl_currency_filter'),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'Intl';
    }
}

function twig_intl_date_filter(Twig_Environment $env,
                               $date,
                               $locale = null,
                               $dateType = IntlDateFormatter::MEDIUM,
                               $timeType = IntlDateFormatter::MEDIUM,
                               $timezone = null,
                               $calendar = IntlDateFormatter::GREGORIAN,
                               $pattern = null)
{
    $constants = array(
        'none'   => IntlDateFormatter::NONE,
        'short'  => IntlDateFormatter::SHORT,
        'medium' => IntlDateFormatter::MEDIUM,
        'long'   => IntlDateFormatter::LONG,
        'full'   => IntlDateFormatter::FULL,
    );

    if (isset($constants[$dateType])) {
        $dateType = $constants[$dateType];
    }

    if (isset($constants[$timeType])) {
        $timeType = $constants[$timeType];
    }

    $date = twig_date_converter($env, $date, $timezone);
    $timezone = $date->getTimeZone()->getName();

    $hash = md5($locale . $dateType . $timeType . $timezone . $pattern);

    static $filters = array();

    if (isset($filters[$hash])) {
        $fmt = $filters[$hash];
    } else {
        $fmt = new IntlDateFormatter(
            $locale,
            $dateType,
            $timeType,
            $timezone,
            $calendar,
            $pattern
        );
        $filters[$hash] = $fmt;
    }

    return $fmt->format($date->getTimestamp());
}

function twig_intl_currency_filter($value, $locale = null, $currency = null, $fractionDigits = null)
{
    $hash = md5($locale . $currency . $fractionDigits);

    static $filters = array();

    if (isset($filters[$hash])) {
        $fmt = $filters[$hash];
    } else {
        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $filters[$hash] = $fmt;
    }

    if (!isset($currency)) {
        $currency = $fmt->getTextAttribute(NumberFormatter::CURRENCY_CODE);
    }

    if (isset($fractionDigits)) {
        $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, (int) $fractionDigits);
    }

    return $fmt->formatCurrency((float) $value, $currency);
}