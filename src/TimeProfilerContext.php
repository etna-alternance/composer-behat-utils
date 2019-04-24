<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;

class TimeProfilerContext implements Context
{
    static private $max_time = 100;

    private $start = 0;
    private $end = 0;

    public function __construct($max_time)
    {
        self::$max_time = $max_time;
    }

    public function setStart()
    {
        $this->start = microtime(true);
    }

    public function setEnd()
    {
        $this->end = microtime(true);
    }

    /**
     * @AfterScenario
     */
    public function stopTimeProfiler(AfterScenarioScope $scope)
    {
        $has_coverage = $scope->getEnvironment()->hasContextClass("ETNA\FeatureContext\CoverageContext");
        if (true === $has_coverage) {
            return;
        }

        $diff = round(($this->end - $this->start) * 1000);
        if ($diff > self::$max_time) {
            echo "{$scope->getFeature()->getFile()}:{$scope->getScenario()->getLine()}\n";
            throw new PendingException("Request too long {$diff}ms > " . self::$max_time . "ms \n");
        }
    }
}
