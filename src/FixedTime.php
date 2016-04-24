<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;

trait FixedTime
{
    static private $_default_date;
    static private $_current_date;

    /**
     * @BeforeSuite
     */
    public static function blackMagicBeforeSuite(SuiteEvent $event)
    {
        $parameters = $event->getContextParameters();

        if (false === isset($parameters['customDate'])) {
            throw new \Exception("You don't have customDate in your behat parameters\n");
        }

        self::$_default_date = self::$_current_date = $parameters['customDate'];
    }

    /**
     * @BeforeScenario
     */
    public function changeTime()
    {
        $_current_date = self::$_current_date;

        uopz_set_return("time", function () use ($_current_date) {
            return strtotime($_current_date);
        }, true);

        uopz_set_return("date", function ($format, $timestamp = null) {
            return date($format, $timestamp ?: time());
        }, true);

        uopz_set_return("strtotime", function ($time, $now = null) {
            return strtotime($time, $now ?: time());
        }, true);
    }

    /**
     * @AfterScenario
     */
    public function resetTime()
    {
        uopz_unset_return("strtotime");
        uopz_unset_return("date");
        uopz_unset_return("time");
    }

    /**
     * @Given /^que la date est "([^"]*)"$/
     */
    public function queLaDateEst($new_date)
    {
        self::$_current_date = $new_date;

        $this->resetTime();
        $this->changeTime();
    }

    /**
     * @Given /^je rollback la date$/
     */
    public function jeRollbackLaDate()
    {
        self::$_current_date = self::$_default_date;

        $this->resetTime();
        $this->changeTime();
    }
}
