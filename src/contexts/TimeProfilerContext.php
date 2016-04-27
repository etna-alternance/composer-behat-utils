<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;

class TimeProfilerContext implements Context
{
    static private $max_time  = 100;
    static private $last_time = null;

    public function __construct($max_time)
    {
        self::$max_time = $max_time;
    }

    /**
     * @BeforeScenario
     */
    public function beginTimeProfiler()
    {
        self::$last_time = round(microtime(true) * 1000);
    }

    /**
     * @AfterScenario
     */
    public function stopTimeProfiler(AfterScenarioScope $scope)
    {
        $has_coverage = $scope->getEnvironment()->hasContextClass("CoverageContext");
        if (true === $has_coverage) {
            return;
        }

        $now  = round(microtime(true) * 1000);
        $diff = $now - self::$last_time;
        if ($diff > self::$max_time) {
            echo "{$scope->getFeature()->getFile()}:{$scope->getScenario()->getLine()}\n";
            throw new PendingException("Request too long {$diff}ms > " . self::$max_time . "ms \n");
        }
    }
}
