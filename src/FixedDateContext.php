<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Event\SuiteEvent;

class FixedDateContext extends BaseContext
{
    private $_date;
    private $_custom_date;

    public function __construct($date)
    {
        $this->_date        = $date;
        $this->_custom_date = $date;
    }

    /**
     * @BeforeScenario
     */
    public function setCustomDate()
    {
        $_custom_date = $this->_custom_date;
        uopz_set_return("time", function () use ($_custom_date) {
            return strtotime($_custom_date);
        }, true);

        uopz_set_return("date", function ($format, $timestamp = null) {
            $timestamp = $timestamp ?: time();
            return date($format, $timestamp);
        }, true);

        uopz_set_return("strtotime", function ($time, $now = null) {
            $now = $now ?: time();
            return strtotime($time, $now);
        }, true);
    }

    /**
     * @AfterScenario
     */
    public function resetToNormalDate()
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
        $this->_custom_date = $new_date;

        $this->resetToNormalDate();
        $this->setCustomDate();
    }

    /**
     * @Given /^je rollback la date$/
     */
    public function jeRollbackLaDate()
    {
        $this->_custom_date = $this->_date;

        $this->resetToNormalDate();
        $this->setCustomDate();
    }
}
