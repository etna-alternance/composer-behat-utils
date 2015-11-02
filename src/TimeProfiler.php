<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;

trait TimeProfiler
{
    static private $max_time  = 100;
    static private $last_time = null;

    /**
     * @BeforeScenario
     */
    public function beginTimeProfiler()
    {
        if (true === isset(self::$_parameters['maxTime']) && self::$_parameters['maxTime']) {
            self::$max_time = self::$_parameters['maxTime'];
        }
        self::$last_time = round(microtime(true) * 1000);
    }

    /**
     * @AfterScenario
     */
    public function stopTimeProfiler($event)
    {
        $now  = round(microtime(true) * 1000);
        $diff = $now - self::$last_time;
        if (false === isset(self::$_parameters['enableCodeCoverage']) && $diff > self::$max_time) {
            if (get_class($event) !== 'Behat\Behat\Event\OutlineExampleEvent') {
                echo "{$event->getScenario()->getFile()}:{$event->getScenario()->getLine()}\n";
            }
            throw new PendingException("Request too long {$diff}ms > " . self::$max_time . "ms \n");
        }
    }
}
