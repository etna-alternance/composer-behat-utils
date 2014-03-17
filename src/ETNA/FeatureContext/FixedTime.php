<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;

function fake_time()
{
    return real_strtotime(self::$_date);
}
function fake_date($format, $timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }
    return real_date($format, $timestamp);
}
function fake_strtotime($time, $now = null)
{
    if ($now === null) {
        $now = time();
    }
    return real_strtotime($time, $now);
}

trait FixedTime
{
    static private $_parameters;
    static private $_date;

    /**
     * @BeforeSuite
     */
    public static function blackMagicBeforeSuite(SuiteEvent $event)
    {
        self::$_parameters = $event->getContextParameters();

        if (isset(self::$_parameters['customDate']) && self::$_parameters['customDate']) {
            self::$_date = self::$_parameters['customDate'];

            $rename = function ($name) {
                runkit_function_rename($name, "real_{$name}");
                runkit_function_rename("ETNA\\FeatureContext\\fake_{$name}", $name);
            };
            $rename("time");
            $rename("date");
            $rename("strtotime");
        } else {
            echo "You don't have customDate in your behat parameters\n";
        }
    }
}
