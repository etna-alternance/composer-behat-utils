<?php

namespace ETNA\FeatureContext;

trait setUpScenarioDirectories
{
    /** @Var string */
    private $requests_path;
    /** @Var string */
    private $results_path;

    /**
     * @BeforeScenario
     */
    public function setUpScenarioDirectories($event)
    {
        if ($event instanceof \Behat\Behat\Event\ScenarioEvent) {
            $this->results_path  = realpath(dirname($event->getScenario()->getFile())) . '/results/';
            $this->requests_path = realpath(dirname($event->getScenario()->getFile())) . '/requests/';
        } else {
            $this->results_path  = realpath(dirname($event->getOutline()->getFile())) . '/results/';
            $this->requests_path = realpath(dirname($event->getOutline()->getFile())) . '/requests/';
        }
    }
}
