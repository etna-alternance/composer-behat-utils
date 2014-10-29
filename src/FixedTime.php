<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;

function fakeTime()
{
    global $custom_date;
    return real_strtotime($custom_date);
}

function fakeDate($format, $timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }
    return real_date($format, $timestamp);
}

function fakeStrtotime($time, $now = null)
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

        if (false === isset($parameters['customDate'])) {
            throw new \Exception("You don't have customDate in your behat parameters\n");
        }

        global $custom_date;
        self::$_date = $custom_date = $parameters['customDate'];

        $rename = function ($real_name, $fake_name) {
            runkit_function_rename($real_name, "real_{$real_name}");
            runkit_function_rename("ETNA\\FeatureContext\\{$fake_name}", $real_name);
        };
        $rename("time", "fakeTime");
        $rename("date", "fakeDate");
        $rename("strtotime", "fakeStrtotime");
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

        $custom_date = self::$_date;
    }
}
