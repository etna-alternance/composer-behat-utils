<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;

class TimeProfilerContext implements Context
{
    private $max_time = 100;
    private $start = 0;
    private $end = 0;
    private $failure = null;

    public function __construct($max_time)
    {
        $this->max_time = $max_time;
    }

    public function start()
    {
        $this->failure = null;
        $this->start   = microtime(true);
    }

    public function stopTimeProfiler()
    {
        $diff = round((microtime(true) - $this->start) * 1000);
        if ($diff > $this->max_time) {
            $this->failure = $diff;
        }
    }

    /**
     * @afterScenario
     */
    public function afterScenarioCheck(AfterScenarioScope $scope)
    {
        if (null !== $this->failure) {
            echo "{$scope->getFeature()->getFile()}:{$scope->getScenario()->getLine()}\n";
            throw new PendingException("Request too long {$this->failure}ms > " . $this->max_time . "ms\n");
        }
    }
}
