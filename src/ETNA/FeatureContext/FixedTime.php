<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;

function fake_time()
{
    global $custom_date;
    return real_strtotime($custom_date);
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
    static private $_date;

    /**
     * @BeforeSuite
     */
    public static function blackMagicBeforeSuite(SuiteEvent $event)
    {
        $parameters = $event->getContextParameters();

        if (isset($parameters['customDate']) && $parameters['customDate']) {
            global $custom_date;
            $custom_date = $parameters['customDate'];

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

    /**
     * @Given /^que la date est "([^"]*)"$/
     */
    public function queLaDateEst($new_date)
    {
        global $custom_date;

        $custom_date = $new_date;
    }

    /**
     * @Given /^je rollback la date$/
     */
    public function jeRollbackLaDate()
    {
        global $custom_date;

        $custom_date = self::$_parameters['customDate'];
    }
}
