<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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
            new Twig_SimpleFilter('intl_number', 'twig_intl_number_filter'),
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

function twig_intl_number_filter($value,
                                 $locale = null,
                                 $style = NumberFormatter::DECIMAL,
                                 $type = NumberFormatter::TYPE_DEFAULT)
{
    $styleConstants = array(
        'decimal'    => NumberFormatter::DECIMAL,
        'percent'    => NumberFormatter::PERCENT,
        'scientific' => NumberFormatter::SCIENTIFIC,
        'spellout'   => NumberFormatter::SPELLOUT,
        'ordinal'    => NumberFormatter::ORDINAL,
        'duration'   => NumberFormatter::DURATION,
    );

    if (isset($styleConstants[$style])) {
        $style = $styleConstants[$style];
    }

    $typeConstants = array(
        'default'  => NumberFormatter::TYPE_DEFAULT,
        'int32'    => NumberFormatter::TYPE_INT32,
        'int64'    => NumberFormatter::TYPE_INT64,
        'double'   => NumberFormatter::TYPE_DOUBLE,
    );

    if (isset($typeConstants[$type])) {
        $type = $typeConstants[$type];
    }

    $hash = md5($locale . $style . $type);

    static $filters = array();

    if (isset($filters[$hash])) {
        $fmt = $filters[$hash];
    } else {
        $fmt = new NumberFormatter($locale, $style);
        $filters[$hash] = $fmt;
    }

    return $fmt->format($value, $type);
}